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
     * @todo filter extensions available for current BE user
     * @return array
     */
    public static function getExtensionsListForTranslate()
    {
        $extensionsWithMetadata = self::getExtensionsWithMetaData();
        ksort($extensionsWithMetadata);

        return array_map(function ($extension) {
            return [$extension['title'], $extension['extensionKey']];
        }, self::getExtensionsWithMetaData());
    }

    /**
     * @return array
     */
    protected static function getExtensionsWithMetaData()
    {
        return array_map(function ($extKey) {
            return MiscUtility::getExtensionMetaData($extKey);
        }, ExtensionManagementUtility::getLoadedExtensionListArray());
    }
}