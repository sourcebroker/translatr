<?php

declare(strict_types=1);

namespace SourceBroker\Translatr\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

final readonly class LanguageFilePathResolver
{
    public function isValidLanguageCode(string $languageCode): bool
    {
        return preg_match('/^(?:default|[a-z]{2,8}(?:[_-][a-z0-9]{1,8})*)$/iD', $languageCode) === 1;
    }

    public function isValidLocallangPath(string $locallangPath): bool
    {
        $relativePath = str_starts_with($locallangPath, 'EXT:')
            ? substr($locallangPath, 4)
            : $locallangPath;

        return $relativePath !== ''
            && GeneralUtility::validPathStr($relativePath)
            && preg_match('#^[a-z0-9_.-]+(?:/[a-z0-9_.-]+)*$#iD', $relativePath) === 1
            && preg_match('#(?:^|/)locallang(?:_db)?\.(?:xlf|xml)$#iD', $relativePath) === 1;
    }

    public function getTargetFilePath(
        string $locallangPath,
        string $languageCode,
        string $overrideFilesBaseDirectoryPath,
    ): string {
        if (!$this->isValidLocallangPath($locallangPath) || !$this->isValidLanguageCode($languageCode)) {
            throw new \InvalidArgumentException('Invalid language file metadata.', 1760540161);
        }

        $isExtensionPath = str_starts_with($locallangPath, 'EXT:');
        $relativePath = $isExtensionPath ? substr($locallangPath, 4) : $locallangPath;
        $namespace = $isExtensionPath ? 'ext' : 'path';
        $basePath = rtrim($overrideFilesBaseDirectoryPath, '/')
            . '/' . $namespace . '/' . $languageCode . '/' . $relativePath;
        $targetPath = dirname($basePath) . '/' . $languageCode . '.' . basename($basePath);
        $targetPath = (string)preg_replace('/\.xml$/i', '.xlf', $targetPath);
        $this->assertPathIsWithin($targetPath, $overrideFilesBaseDirectoryPath);

        return $targetPath;
    }

    /**
     * @return array{language: string, overwritten: string}|null
     */
    public function resolveGeneratedFile(string $filePath, string $overrideFilesBaseDirectoryPath): ?array
    {
        $basePath = PathUtility::getCanonicalPath($overrideFilesBaseDirectoryPath);
        $filePath = PathUtility::getCanonicalPath($filePath);
        if (!str_starts_with($filePath, $basePath . '/')) {
            return null;
        }

        $pathParts = explode('/', substr($filePath, strlen($basePath) + 1));
        if (count($pathParts) < 4 || !in_array($pathParts[0], ['ext', 'path'], true)) {
            return null;
        }

        $namespace = array_shift($pathParts);
        $languageCode = (string)array_shift($pathParts);
        if (!$this->isValidLanguageCode($languageCode)) {
            return null;
        }

        $fileName = (string)array_pop($pathParts);
        $languagePrefix = $languageCode . '.';
        if (!str_starts_with($fileName, $languagePrefix)) {
            return null;
        }
        $pathParts[] = substr($fileName, strlen($languagePrefix));
        $overwritten = implode('/', $pathParts);
        if ($namespace === 'ext') {
            $overwritten = 'EXT:' . $overwritten;
        }
        if (!$this->isValidLocallangPath($overwritten)) {
            return null;
        }

        return [
            'language' => $languageCode,
            'overwritten' => $overwritten,
        ];
    }

    public function mapToPublishedPath(string $stagingPath, string $stagingFolderPath, string $publishedFolderPath): string
    {
        $this->assertPathIsWithin($stagingPath, $stagingFolderPath);
        $stagingPath = PathUtility::getCanonicalPath($stagingPath);
        $stagingFolderPath = PathUtility::getCanonicalPath($stagingFolderPath);

        return rtrim($publishedFolderPath, '/') . substr($stagingPath, strlen($stagingFolderPath));
    }

    public function assertPathIsWithin(string $path, string $basePath): void
    {
        $basePath = PathUtility::getCanonicalPath($basePath);
        $path = PathUtility::getCanonicalPath($path);
        if ($path !== $basePath && !str_starts_with($path, $basePath . '/')) {
            throw new \RuntimeException('Generated language file path is outside its working folder.', 1760540163);
        }
    }
}
