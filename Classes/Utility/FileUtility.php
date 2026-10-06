<?php

declare(strict_types=1);

namespace SourceBroker\Translatr\Utility;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileUtility
{
    public static function getTempFolderPath(bool $create = true): string
    {
        $tempFolderPath = Environment::getVarPath() . '/cache/data/tx_translatr';
        if ($create && !is_dir($tempFolderPath)) {
            GeneralUtility::mkdir_deep($tempFolderPath);
        }

        return $tempFolderPath;
    }
}
