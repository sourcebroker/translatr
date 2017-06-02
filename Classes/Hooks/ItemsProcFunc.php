<?php

namespace SourceBroker\Translatr\Hooks;

use SourceBroker\Translatr\Utility\PluginsUtility;

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
    public function getPluginsList(&$conf)
    {
        $conf['items'] = PluginsUtility::getPluginsListForTranslate();
    }

}