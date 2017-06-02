<?php

namespace SourceBroker\Translatr\Hooks;

use SourceBroker\Translatr\Utility\ExtensionsUtility;

/**
 * Class ItemsProcFunc
 *
 * @package SourceBroker\Translatr\Hooks
 */
class ItemsProcFunc
{
    /**
     * @param array $conf
     */
    public function getExtensionsList(&$conf)
    {
        $conf['items'] = ExtensionsUtility::getExtensionsListForTranslate();
    }

}