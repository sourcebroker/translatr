<?php

namespace SourceBroker\Translatr\Utility;

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
        $tempFolderPath = PATH_site . 'uploads/tx_translatr';
        if (!is_dir($tempFolderPath)) {
            GeneralUtility::mkdir_deep($tempFolderPath);
        }
        return $tempFolderPath;
    }
}
