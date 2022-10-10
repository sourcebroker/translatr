<?php

namespace SourceBroker\Translatr\Utility;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class FileUtility
 *
 */
class FileUtility
{
    /**
     * Returns relative path to the $filePath
     *
     * @param $path
     *
     * @return string
     */
    public static function getRelativePathFromAbsolute($path)
    {
        if (GeneralUtility::isAbsPath($path)) {
            $replacements = [
                Environment::getPublicPath() . DIRECTORY_SEPARATOR . 'typo3conf' . DIRECTORY_SEPARATOR . 'ext'
                . DIRECTORY_SEPARATOR => 'EXT:',
                Environment::getPublicPath() . DIRECTORY_SEPARATOR . 'typo3conf' . DIRECTORY_SEPARATOR => 'typo3conf',
                Environment::getPublicPath() . DIRECTORY_SEPARATOR => '',
            ];
            foreach ($replacements as $replaceFrom => $replaceTo) {
                if (GeneralUtility::isFirstPartOfStr($path, $replaceFrom)) {
                    $path = str_replace($replaceFrom, $replaceTo, $path);
                }
            }
        }
        return $path;
    }

    /**
     * @return string
     */
    public static function getTempFolderPath()
    {
        $tempFolderPath = Environment::getVarPath() . '/tx_translatr';
        if (!is_dir($tempFolderPath)) {
            GeneralUtility::mkdir_deep($tempFolderPath);
        }

        return $tempFolderPath;
    }
}
