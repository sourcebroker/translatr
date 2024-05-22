<?php

namespace SourceBroker\Translatr\Utility;

use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileUtility
{
    public static function getRelativePathFromAbsolute(string $path, string $extKey): ?string
    {
        $out = null;
        if (PathUtility::isAbsolutePath($path)) {
            $packageManager = GeneralUtility::makeInstance(PackageManager::class);
            $absolutePathToExtension = $packageManager->getPackage($extKey)->getPackagePath();
            $out = 'EXT:' . $extKey . '/' . str_replace($absolutePathToExtension, '', $path);
        }
        return $out;
    }

    public static function getTempFolderPath(): string
    {
        $tempFolderPath = Environment::getVarPath() . '/cache/data/tx_translatr';
        if (!is_dir($tempFolderPath)) {
            GeneralUtility::mkdir_deep($tempFolderPath);
        }

        return $tempFolderPath;
    }
}
