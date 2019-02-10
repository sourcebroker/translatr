<?php

namespace SourceBroker\Translatr\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class FileUtility
 *
 * @package SourceBroker\Translatr\Utility
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
                PATH_site . 'typo3conf' . DIRECTORY_SEPARATOR . 'ext'
                . DIRECTORY_SEPARATOR => 'EXT:',
                PATH_site . 'typo3conf' . DIRECTORY_SEPARATOR => 'typo3conf',
                PATH_site => '',
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
        switch (substr(TYPO3_version, 0, 1)) {
            case 9:
                $cachePath = 'var/cache/data/txtranslatr';
                break;
            case 8:
                $cachePath = 'var/Cache/Data/txtranslatr';
                break;
            case 7:
                $cachePath = 'Cache/Data/txtranslatr';
                break;
            default:
                $cachePath = 'Cache/txtranslatr';
        }
        $tempFolderPath = PATH_site . 'typo3temp/' . $cachePath;
        if (!is_dir($tempFolderPath)) {
            GeneralUtility::mkdir_deep($tempFolderPath);
        }
        return $tempFolderPath;
    }
}
