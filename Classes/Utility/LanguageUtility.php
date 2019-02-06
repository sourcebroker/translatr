<?php

namespace SourceBroker\Translatr\Utility;

use SourceBroker\Translatr\Configuration\Configurator;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
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
        $conf = GeneralUtility::makeInstance(Configurator::class);
        return $conf->getOption('languages');
    }

    /**
     * @param $file
     * @param $language
     * @return array|bool
     */
    public static function parseLanguageLabels($file, $language)
    {
        if (MiscUtility::isTypo39up()) {
            $languageFactory = GeneralUtility::makeInstance(LocalizationFactory::class);
            $parsedLabels = $languageFactory->getParsedData($file, $language);
        } else {
            $parsedLabels = $GLOBALS['LANG']->getLanguageService()
                ->parserFactory
                ->getParsedData($file, $language);
        }
        return $parsedLabels;
    }
}
