<?php

namespace SourceBroker\Translatr\Utility;

use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class LanguageUtility
 *
 * @package SourceBroker\Translatr\Utility
 */
class LanguageUtility
{

    /**
     * @return array
     */
    public static function getAvailableLanguages()
    {
        $languages = self::getLocales()->getLanguages();
        unset($languages['default']);
        asort($languages);
        return $languages;
    }

    /**
     * @return Locales
     */
    protected static function getLocales()
    {
        return GeneralUtility::makeInstance(Locales::class);
    }
}
