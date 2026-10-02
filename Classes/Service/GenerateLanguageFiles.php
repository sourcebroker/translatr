<?php

namespace SourceBroker\Translatr\Service;

use SourceBroker\Translatr\Utility\FileUtility;
use SourceBroker\Translatr\Database\Database;
use SourceBroker\Translatr\Utility\ExceptionUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
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
    protected string $overrideFilesExtDirectoryPath;


    public function initialize(): void
    {
        $this->tempFolderPath = FileUtility::getTempFolderPath();
        if (is_link($this->tempFolderPath)) {
            // Publish into the symlink target, renaming the symlink itself would replace it with a folder
            $this->tempFolderPath = realpath($this->tempFolderPath);
        }
        $loaderFilePath = $this->tempFolderPath . '/' . self::LOADER_FILE_NAME;

        // Fast path without lock. A flush (see CacheCleaner) can remove the file right after the check,
        // so the include is silenced and in case of failure the files are generated under the lock.
        if (file_exists($loaderFilePath) && (@include $loaderFilePath) !== false) {
            return;
        }

        $locker = GeneralUtility::makeInstance(Locker::class);
        $locker->acquire();
        try {
            if (!file_exists($loaderFilePath)) {
                $this->generate();
            }
            include $loaderFilePath;
        } finally {
            $locker->release();
        }
    }

    /**
     * Generates all files in a staging folder and publishes it with one rename, so other processes never
     * see a partially generated folder.
     */
    protected function generate(): void
    {
        $this->stagingFolderPath = $this->tempFolderPath . '.' . StringUtility::getUniqueId('build');
        try {
            $this->setOverrideFilesLoaderFilePath();
            $this->setOverrideFilesBaseDirectoryPath();
            $this->setOverrideFilesExtDirectoryPath();
            $this->createNotExistingLocallangOverrideFiles();
            $this->createOverrideFilesLoaderFileIfNotExists();
            if (!$this->overrideFilesLoaderFileExists()) {
                ExceptionUtility::throwException(
                    \RuntimeException::class,
                    'Could not create locallang XML Override file in path '
                    . $this->overrideFilesLoaderFilePath . ' due to unknown reason.',
                    82347523
                );
            }
            $this->publishStagingFolder();
        } finally {
            if (is_dir($this->stagingFolderPath)) {
                GeneralUtility::rmdir($this->stagingFolderPath, true);
            }
        }
    }

    protected function publishStagingFolder(): void
    {
        // The temp folder has no loader file here (e.g. it is empty after a flush) - move it away to free the name
        $removeFolderPath = $this->tempFolderPath . '.' . StringUtility::getUniqueId('remove');
        if (is_dir($this->tempFolderPath) && !rename($this->tempFolderPath, $removeFolderPath)) {
            ExceptionUtility::throwException(
                \RuntimeException::class,
                'Could not move folder ' . $this->tempFolderPath . ' to ' . $removeFolderPath,
                1791021301
            );
        }
        // A concurrent FileUtility::getTempFolderPath() may have recreated the folder meanwhile, but it is empty
        // then and rename() replaces an empty target folder.
        if (!rename($this->stagingFolderPath, $this->tempFolderPath)) {
            ExceptionUtility::throwException(
                \RuntimeException::class,
                'Could not move folder ' . $this->stagingFolderPath . ' to ' . $this->tempFolderPath,
                1791021302
            );
        }
        GeneralUtility::makeInstance(OpcodeCacheService::class)
            ->clearAllActive($this->tempFolderPath . '/' . self::LOADER_FILE_NAME);
        if (is_dir($removeFolderPath)) {
            GeneralUtility::rmdir($removeFolderPath, true);
        }
    }

    /**
     * Maps a path inside the staging folder to its path after publishing.
     */
    protected function getPublishedPath(string $stagingPath): string
    {
        return $this->tempFolderPath . substr($stagingPath, strlen($this->stagingFolderPath));
    }

    protected function setOverrideFilesLoaderFilePath(): void
    {
        $this->overrideFilesLoaderFilePath = $this->stagingFolderPath . '/' . self::LOADER_FILE_NAME;
    }

    protected function setOverrideFilesBaseDirectoryPath(): void
    {
        $this->overrideFilesBaseDirectoryPath = $this->stagingFolderPath . '/overrides';
    }

    protected function setOverrideFilesExtDirectoryPath(): void
    {
        $this->overrideFilesExtDirectoryPath = $this->overrideFilesBaseDirectoryPath . '/ext';
    }

    protected function overrideFilesLoaderFileExists(): bool
    {
        return file_exists($this->overrideFilesLoaderFilePath);
    }

    protected function createOverrideFilesLoaderFileIfNotExists(): void
    {
        if ($this->overrideFilesLoaderFileExists()) {
            return;
        }

        $this->createOverrideFilesLoaderFile();
    }

    protected function createOverrideFilesLoaderFile(): void
    {

        $this->createOverrideFilesLoaderFileDirectoryIfNotExists();
        $this->createOverrideFilesDirectories();
        $code = '<?php' . PHP_EOL;
        foreach ($this->getTranslationOverrideFiles() as $isoCode => $fileDatasets) {
            foreach ($fileDatasets as $fileData) {
                $code .= $this->getFinalOverrideRow($isoCode, $fileData['overwritten'], $fileData['overwriteWith']);
                $code .= $this->getFinalOverrideRow($isoCode,
                    str_replace('EXT:', 'typo3conf/ext/', $fileData['overwritten']), $fileData['overwriteWith']);
            }
        }
        $tempFilename = $this->overrideFilesLoaderFilePath . '.tmp';
        if (!file_put_contents($tempFilename, $code)) {
            ExceptionUtility::throwException(
                \RuntimeException::class,
                'Could not write file in ' . $tempFilename,
                390847534
            );
        }
        GeneralUtility::fixPermissions($tempFilename, true);
        rename($tempFilename, $this->overrideFilesLoaderFilePath);
    }

    protected function getFinalOverrideRow($isoCode, $overwritten, $overwriteWith)
    {
        // TYPO3 14 moved SYS/locallangXMLOverride to LANG/resourceOverrides (#107436)
        $overridesPath = (new Typo3Version())->getMajorVersion() >= 14
            ? '[\'LANG\'][\'resourceOverrides\']'
            : '[\'SYS\'][\'locallangXMLOverride\']';
        return '$GLOBALS[\'TYPO3_CONF_VARS\']' . $overridesPath . '[\'' . $isoCode . '\'][\''
            . $overwritten . '\'][] = \'' . $overwriteWith . '\';' . PHP_EOL;
    }

    protected function createOverrideFilesLoaderFileDirectoryIfNotExists(): void
    {
        $this->createDirectoryIfNotExists(dirname($this->overrideFilesLoaderFilePath));
    }

    protected function createOverrideFilesDirectories(): void
    {
        $this->createOverrideFilesBaseDirectoryIfNotExists();
        $this->createOverrideFilesExtDirectoryIfNotExists();
    }

    protected function createOverrideFilesBaseDirectoryIfNotExists(): void
    {
        $this->createDirectoryIfNotExists($this->overrideFilesExtDirectoryPath);
    }

    protected function createOverrideFilesExtDirectoryIfNotExists(): void
    {
        $this->createDirectoryIfNotExists($this->overrideFilesExtDirectoryPath);
    }

    protected function createDirectoryIfNotExists(string $directoryPath): void
    {
        if (!is_dir($directoryPath)) {
            GeneralUtility::mkdir_deep($directoryPath);
            if (!is_dir($directoryPath)) {
                ExceptionUtility::throwException(
                    \RuntimeException::class,
                    'Could not create directory in ' . $directoryPath,
                    938457943
                );
            }
        }
    }

    /**
     * @todo check if return of relative path (in element value path) works fine. It will be better to return relative path to avoid problems with some specific server settings
     */
    protected function getTranslationOverrideFiles(): array
    {
        $translationOverrideFiles = [];

        $files = new \RegexIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->overrideFilesBaseDirectoryPath)
            ),
            '/locallang(|_db)\.xlf|locallang(|_db)\.xml/',
            \RegexIterator::GET_MATCH
        );

        foreach ($files as $fullPath => $file) {
            $isoCode = explode('/', substr($fullPath, strlen($this->overrideFilesBaseDirectoryPath . '/')))[1];
            $translationOverrideFiles[$isoCode][] = [
                'overwritten' => $this->transformPathFromLocallangOverridesToLocallang($fullPath),
                'overwriteWith' => str_replace(Environment::getPublicPath() . '/', '', $this->getPublishedPath($fullPath))
            ];
        }
        return $translationOverrideFiles;
    }

    protected function transformPathFromLocallangOverridesToLocallang(string $fullPath): string
    {
        $replacements = [
            $this->overrideFilesExtDirectoryPath => 'EXT:',
            $this->overrideFilesBaseDirectoryPath => '',
        ];
        $pathInfo = pathinfo(explode(':', str_replace(array_keys($replacements), $replacements, $fullPath))[1]);
        $dirnameExploded = explode(DIRECTORY_SEPARATOR, $pathInfo['dirname']);
        array_shift($dirnameExploded);
        array_shift($dirnameExploded);
        $pathNoIso = implode(DIRECTORY_SEPARATOR, $dirnameExploded);

        $nameExploded = explode('.', $pathInfo['basename']);
        array_shift($nameExploded);
        $nameNoIso = implode('.', $nameExploded);

        return 'EXT:' . $pathNoIso . '/' . $nameNoIso;
    }

    protected function transformPathFromLocallangToLocallangOverrides(string $locallangPath, string $isocode): string
    {
        if (\str_starts_with($locallangPath, 'EXT:')) {
            return str_replace('EXT:', $this->overrideFilesExtDirectoryPath . '/' . $isocode . '/', $locallangPath);
        }
        return $this->overrideFilesBaseDirectoryPath . '/' . $locallangPath;
    }

    protected function createNotExistingLocallangOverrideFiles(): void
    {
        if (false === file_exists($this->overrideFilesLoaderFilePath)) {
            $locallangFiles =
                GeneralUtility::makeInstance(Database::class)
                    ->getLocallangFiles();
            if (!$locallangFiles) {
                return;
            }
            foreach ($locallangFiles as $locallangFile) {
                $this->createLocallangOverrideFileIfNotExist($locallangFile['ll_file'], $locallangFile['language']);
            }
        }
    }

    protected function createLocallangOverrideFileIfNotExist(string $locallangFile, string $isoCode): void
    {
        $locallangOverrideFilePath = $this->transformPathFromLocallangToLocallangOverrides($locallangFile, $isoCode);
        $finalFilePath = $this->prependLocallangFileNameWithIsoCode($locallangOverrideFilePath, $isoCode);
        $finalFilePath = preg_replace('/\.xml$/i', '.xlf', $finalFilePath);

        if (!is_file($finalFilePath)) {
            $this->createLocallangOverrideFile($locallangFile, $isoCode);
        }
    }

    protected function createLocallangOverrideFile(string $locallangFile, ?string $isoCode = null): void
    {
        $labels = $this->getLabelsByLocallangFile($locallangFile);
        $groupedLabels = [];

        foreach ($labels as $label) {
            $groupedLabels[$label['isocode']][] = $label;
        }

        unset($labels);

        $languagesToProcess = $isoCode !== null ? [$isoCode => $groupedLabels[$isoCode] ?? []] : $groupedLabels;

        foreach ($languagesToProcess as $currentIsoCode => $labels) {
            if (empty($labels)) {
                continue;
            }

            $xml = $this->createXlfFileForLabels($labels, $currentIsoCode);
            $xml->formatOutput = true;
            $defaultLocallangOverrideFile = $this->transformPathFromLocallangToLocallangOverrides(
                $locallangFile,
                $currentIsoCode
            );
            $outputFiles = [
                $this->prependLocallangFileNameWithIsoCode($defaultLocallangOverrideFile, $currentIsoCode)
            ];
            foreach ($outputFiles as $outputFile) {
                $this->createDirectoryIfNotExists(dirname($outputFile));
                file_put_contents($outputFile, $xml->saveXML());
                $pathParts = pathinfo($outputFile);
                GeneralUtility::fixPermissions($outputFile, true);
                rename($outputFile, $pathParts['dirname'] . '/' . $pathParts['filename'] . '.xlf');
            }
        }
    }

    protected function createXlfFileForLabels(array $labels, string $isoCode = 'default'): \DOMDocument
    {
        $xml = new \DOMDocument('1.0', 'utf-8');
        $root = $xml->createElement('xliff');
        $xml->appendChild($root);
        $root->setAttribute('version', '1.0');

        $file = $xml->createElement('file');
        $root->appendChild($file);
        $file->setAttribute('source-language', 'en');
        if ($isoCode !== 'default') {
            // TYPO3 14 reads <target> only from files with a "target-language" attribute, <source> otherwise
            $file->setAttribute('target-language', $isoCode);
        }
        $file->setAttribute('datatype', 'plaintext');
        $file->setAttribute('original', 'messages');
        $file->setAttribute('date', (new \DateTime())->format('c'));
        $file->setAttribute('product', ''); // @todo enter $labels[{n}]['extension'] here

        $fileHeader = $xml->createElement('header');
        $file->appendChild($fileHeader);

        $fileBody = $xml->createElement('body');
        $file->appendChild($fileBody);

        foreach ($labels as $label) {
            $transUnit = $xml->createElement('trans-unit');
            $transUnit->setAttribute('id', $label['ukey']);

            if ($label['isocode'] == 'default') {
                $source = $xml->createElement('source');
                $transUnit->appendChild($source);
                $source->appendChild(
                    $xml->createCDATASection($label['text'])
                );
            } else {
                $target = $xml->createElement('target');
                $transUnit->appendChild($target);
                $target->appendChild(
                    $xml->createCDATASection($label['text'])
                );
            }
            $fileBody->appendChild($transUnit);
        }

        return $xml;
    }

    protected function prependLocallangFileNameWithIsoCode(string $filePath, string $isoCode): string
    {
        $fileName = basename($filePath);
        $dirname = dirname($filePath);
        return $dirname . '/' . $isoCode . '.' . $fileName;
    }

    protected function getLabelsByLocallangFile(string $locallangFile): array
    {
        return
            GeneralUtility::makeInstance(Database::class)
                ->getLabelsByLocallangFile($locallangFile);
    }

}
