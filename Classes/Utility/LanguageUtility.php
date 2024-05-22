<?php

namespace SourceBroker\Translatr\Utility;

use SourceBroker\Translatr\Configuration\Configurator;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LanguageUtility
{
    public static function getAvailableLanguages(): array
    {
        $conf = GeneralUtility::makeInstance(Configurator::class);
        return $conf->getOption('languages');
    }

    public static function parseLanguageLabels(string $file, string $language): array
    {
        $languageFactory = GeneralUtility::makeInstance(LocalizationFactory::class);
        return $languageFactory->getParsedData($file, $language);
    }
}
