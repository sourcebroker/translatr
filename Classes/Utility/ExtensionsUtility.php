<?php

namespace SourceBroker\Translatr\Utility;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Documentation\Utility\MiscUtility;

/**
 * Class ExtensionsUtility
 *
 * @package SourceBroker\Translatr\Utility
 */
class ExtensionsUtility
{

    /**
     * @return array
     */
    public static function getExtensionsWithMetaData()
    {
        return array_map(function ($extKey) {
            return MiscUtility::getExtensionMetaData($extKey);
        }, ExtensionManagementUtility::getLoadedExtensionListArray());
    }
}