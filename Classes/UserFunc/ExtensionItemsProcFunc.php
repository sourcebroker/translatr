<?php

namespace SourceBroker\Translatr\UserFunc;

use SourceBroker\Translatr\Utility\ExtensionsUtility;

/**
 * Class ExtensionItemsProcFunc
 *
 * @package SourceBroker\Translatr\Hooks
 */
class ExtensionItemsProcFunc
{
    /**
     * @param array $config
     */
    public function getItems(&$config)
    {
        $extensionsWithMetadata = ExtensionsUtility::getExtensionsWithMetaData();
        ksort($extensionsWithMetadata);

        $config['items'] += array_map(function ($extension) {
            return [$extension['title'], $extension['extensionKey']];
        }, $extensionsWithMetadata);
    }

}