<?php

declare(strict_types=1);

namespace SourceBroker\Translatr\Service;

use SourceBroker\Translatr\Database\LabelReader;
use SourceBroker\Translatr\Domain\Exception\LanguageFileGenerationException;
use SourceBroker\Translatr\Utility\FileUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

class GenerateLanguageFiles
{
    protected const LOADER_FILE_NAME = 'locallangOverrideLoader.php';

    protected string $tempFolderPath;
    protected string $stagingFolderPath;
    protected string $overrideFilesLoaderFilePath;
    protected string $overrideFilesBaseDirectoryPath;

    public function __construct(
        private readonly LanguageFilePathResolver $pathResolver,
        private readonly OverrideLoaderContentBuilder $loaderContentBuilder,
        private readonly XlfBuilder $xlfBuilder,
        private readonly LabelReader $labelReader,
        private readonly Locker $locker,
        private readonly OpcodeCacheService $opcodeCacheService,
        private readonly GenerationRetry $generationRetry,
    ) {}

    public function initialize(): void
    {
        // Fast path without lock. A flush can remove the file right after the check, so a failed include falls
        // through to generation under the lock.
        if ($this->loadPublishedFiles()) {
            return;
        }
        $production = Environment::getContext()->isProduction();
        if ($production && $this->generationRetry->isDeferred()) {
            return;
        }

        $this->locker->acquire();
        try {
            // Another request may have failed while this request was waiting for the lock.
            if ($this->loadPublishedFiles() || ($production && $this->generationRetry->isDeferred())) {
                return;
            }
            try {
                $this->tempFolderPath = FileUtility::getTempFolderPath();
            } catch (\RuntimeException $exception) {
                throw new LanguageFileGenerationException('Could not create the language file cache folder.', 1791214402, $exception);
            }
            if (is_link($this->tempFolderPath)) {
                $resolvedPath = realpath($this->tempFolderPath);
                if ($resolvedPath === false) {
                    throw new LanguageFileGenerationException('Could not resolve the language file cache folder.', 1760540164);
                }
                $this->tempFolderPath = $resolvedPath;
            }
            $loaderFilePath = $this->tempFolderPath . '/' . self::LOADER_FILE_NAME;
            if (!file_exists($loaderFilePath)) {
                $this->generate();
            }
            if ((@include $loaderFilePath) === false) {
                throw new LanguageFileGenerationException('Could not load generated language overrides.', 1791214403);
            }
            $this->generationRetry->reset();
        } catch (LanguageFileGenerationException $exception) {
            if ($production) {
                $this->generationRetry->defer();
            }
            throw $exception;
        } finally {
            $this->locker->release();
        }
    }

    /** @phpstan-impure */
    public function loadPublishedFiles(): bool
    {
        $loader = FileUtility::getTempFolderPath(false) . '/' . self::LOADER_FILE_NAME;
        return is_file($loader) && (@include $loader) !== false;
    }

    /**
     * Builds a complete set in a staging folder before replacing the published folder under the lock.
     */
    protected function generate(): void
    {
        $this->stagingFolderPath = $this->tempFolderPath . '.' . StringUtility::getUniqueId('build');
        $this->overrideFilesLoaderFilePath = $this->stagingFolderPath . '/' . self::LOADER_FILE_NAME;
        $this->overrideFilesBaseDirectoryPath = $this->stagingFolderPath . '/overrides';
        try {
            $this->createDirectoryIfNotExists($this->overrideFilesBaseDirectoryPath);
            $this->createLocallangOverrideFiles();
            $this->createOverrideFilesLoaderFile();
            $this->publishStagingFolder();
        } finally {
            if (is_dir($this->stagingFolderPath)) {
                GeneralUtility::rmdir($this->stagingFolderPath, true);
            }
        }
    }

    protected function publishStagingFolder(): void
    {
        $removeFolderPath = $this->tempFolderPath . '.' . StringUtility::getUniqueId('remove');
        if (is_dir($this->tempFolderPath) && !@rename($this->tempFolderPath, $removeFolderPath)) {
            throw new LanguageFileGenerationException(
                'Could not move folder ' . $this->tempFolderPath . ' to ' . $removeFolderPath,
                1791021301
            );
        }
        if (!@rename($this->stagingFolderPath, $this->tempFolderPath)) {
            if (is_dir($removeFolderPath) && !@rename($removeFolderPath, $this->tempFolderPath)) {
                throw new LanguageFileGenerationException(
                    'Could not publish language files or restore the previous folder. Previous files remain in ' . $removeFolderPath,
                    1791021303
                );
            }
            throw new LanguageFileGenerationException(
                'Could not move folder ' . $this->stagingFolderPath . ' to ' . $this->tempFolderPath,
                1791021302
            );
        }
        $this->opcodeCacheService->clearAllActive($this->tempFolderPath . '/' . self::LOADER_FILE_NAME);
        if (is_dir($removeFolderPath)) {
            GeneralUtility::rmdir($removeFolderPath, true);
        }
    }

    protected function createOverrideFilesLoaderFile(): void
    {
        $code = $this->loaderContentBuilder->build($this->getTranslationOverrideFiles());
        $tempFilename = $this->overrideFilesLoaderFilePath . '.tmp';
        if (@file_put_contents($tempFilename, $code) !== strlen($code)) {
            throw new LanguageFileGenerationException(
                'Could not write file in ' . $tempFilename,
                390847534
            );
        }
        GeneralUtility::fixPermissions($tempFilename, true);
        if (!@rename($tempFilename, $this->overrideFilesLoaderFilePath)) {
            throw new LanguageFileGenerationException(
                'Could not publish language file loader ' . $this->overrideFilesLoaderFilePath,
                1760540165
            );
        }
    }

    protected function createDirectoryIfNotExists(string $directoryPath): void
    {
        $this->pathResolver->assertPathIsWithin($directoryPath, $this->stagingFolderPath);
        if (!is_dir($directoryPath)) {
            try {
                GeneralUtility::mkdir_deep($directoryPath);
            } catch (\RuntimeException $exception) {
                throw new LanguageFileGenerationException('Could not create directory in ' . $directoryPath, 938457943, $exception);
            }
            if (!is_dir($directoryPath)) {
                throw new LanguageFileGenerationException(
                    'Could not create directory in ' . $directoryPath,
                    938457943
                );
            }
        }
    }

    /**
     * @return array<string, list<array{overwritten: string, overwriteWith: string}>>
     */
    protected function getTranslationOverrideFiles(): array
    {
        $translationOverrideFiles = [];
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->overrideFilesBaseDirectoryPath)
        );

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $fileData = $this->pathResolver->resolveGeneratedFile(
                $file->getPathname(),
                $this->overrideFilesBaseDirectoryPath,
            );
            if ($fileData === null) {
                continue;
            }
            $publishedPath = $this->pathResolver->mapToPublishedPath(
                $file->getPathname(),
                $this->stagingFolderPath,
                $this->tempFolderPath,
            );
            $translationOverrideFiles[$fileData['language']][] = [
                'overwritten' => $fileData['overwritten'],
                'overwriteWith' => str_replace(Environment::getPublicPath() . '/', '', $publishedPath),
            ];
        }

        return $translationOverrideFiles;
    }

    protected function createLocallangOverrideFiles(): void
    {
        $locallangFiles = $this->labelReader->getLocallangFiles();
        if ($locallangFiles === []) {
            return;
        }

        $isoCodesByLocallangFile = [];
        $usedOverrideFiles = [];
        foreach ($locallangFiles as $locallangFile) {
            $llFile = (string)($locallangFile['ll_file'] ?? '');
            $language = (string)($locallangFile['language'] ?? '');
            if (!$this->pathResolver->isValidLocallangPath($llFile)
                || !$this->pathResolver->isValidLanguageCode($language)
            ) {
                continue;
            }
            $overrideFile = (string)preg_replace('/\.xml$/i', '.xlf', $llFile);
            if (isset($usedOverrideFiles[$overrideFile][$language])) {
                continue;
            }
            $usedOverrideFiles[$overrideFile][$language] = true;
            $isoCodesByLocallangFile[$llFile][] = $language;
        }

        foreach ($isoCodesByLocallangFile as $locallangFile => $isoCodes) {
            $this->createLocallangOverrideFile((string)$locallangFile, $isoCodes);
        }
    }

    /**
     * @param list<string> $isoCodes
     */
    protected function createLocallangOverrideFile(string $locallangFile, array $isoCodes): void
    {
        $groupedLabels = [];
        foreach ($this->getLabelsByLocallangFile($locallangFile) as $label) {
            $languageCode = (string)($label['isocode'] ?? '');
            if ($this->pathResolver->isValidLanguageCode($languageCode)) {
                $groupedLabels[$languageCode][] = $label;
            }
        }

        foreach ($isoCodes as $languageCode) {
            $labels = $groupedLabels[$languageCode] ?? [];
            if ($labels === []) {
                continue;
            }

            $outputFile = $this->pathResolver->getTargetFilePath(
                $locallangFile,
                $languageCode,
                $this->overrideFilesBaseDirectoryPath,
            );
            $this->createDirectoryIfNotExists(dirname($outputFile));
            $xml = $this->xlfBuilder->build($labels, $languageCode);
            $xmlContent = $xml->saveXML();
            if ($xmlContent === false || @file_put_contents($outputFile, $xmlContent) !== strlen($xmlContent)) {
                throw new LanguageFileGenerationException(
                    'Could not write language file ' . $outputFile,
                    1760540166
                );
            }
            GeneralUtility::fixPermissions($outputFile, true);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function getLabelsByLocallangFile(string $locallangFile): array
    {
        return $this->labelReader->getLabelsByLocallangFile($locallangFile);
    }
}
