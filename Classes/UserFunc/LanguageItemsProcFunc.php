<?php

namespace SourceBroker\Translatr\UserFunc;

use SourceBroker\Translatr\Utility\LanguageUtility;

/**
 * Class LanguageItemsProcFunc
 *
 */
class LanguageItemsProcFunc
{

    /**
     * @param array $config
     */
    public function getItems(&$config)
    {
        $items = [];
        foreach (LanguageUtility::getAvailableLanguages() as $iso => $language) {
            $items[] = [$language, $iso];
        }

        $config['items'] += $items;
    }
}
