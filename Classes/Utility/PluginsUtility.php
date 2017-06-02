<?php

namespace SourceBroker\Translatr\Utility;

/**
 * Class PluginsUtility
 *
 * @package SourceBroker\Translatr\Utility
 */
class PluginsUtility
{

    /**
     * @return array
     */
    public static function getPluginsListForTranslate()
    {
        $items = $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'];
        foreach ($items as $index => &$item) {
            if (empty($item[1])) {
                unset($items[$index]);
                continue;
            }

            $item[0] = self::getLanguageService()->sL($item[0]).' ('.$item[1].')';
        }

        // add custom plugins set in translate extension configuration
        foreach (EmConfiguration::getSettings()->getCustomPlugins() as $customplugin) {
            $items[] = [$customplugin['label'].' ('.$customplugin['plugin'].')', $customplugin['plugin']];
        }

        return $items;
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    private static function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}