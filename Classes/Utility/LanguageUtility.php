<?php

namespace SourceBroker\Translatr\Utility;

use SourceBroker\Translatr\Configuration\Configurator;
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
}
