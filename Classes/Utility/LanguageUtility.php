<?php

namespace SourceBroker\Translatr\Utility;

use SourceBroker\Translatr\Configuration\Configurator;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LanguageUtility
{
    /**
     * @return array<string, string>|null
     */
    public static function getAvailableLanguages(): ?array
    {
        $conf = GeneralUtility::makeInstance(Configurator::class);
        return $conf->getOption('languages');
    }

    /**
     * Returns labels in the TYPO3 13 structure for all supported TYPO3 versions:
     * [$language => [$key => [['source' => '...', 'target' => '...']]]]
     *
     * @return array<string, mixed>
     */
    public static function parseLanguageLabels(string $file, string $language): array
    {
        $languageFactory = GeneralUtility::makeInstance(LocalizationFactory::class);
        if ((new Typo3Version())->getMajorVersion() < 14) {
            return $languageFactory->getParsedData($file, $language);
        }

        // TYPO3 14 (#107436) returns a flat [$key => $text] array, with values of the fallback
        // languages ("en", "default") merged in for keys which are not translated.
        $labels = $languageFactory->getParsedData($file, $language);
        $defaultLabels = $language === 'default' ? $labels : $languageFactory->getParsedData($file, 'default');
        $parsedLabels = [];
        foreach ($labels as $key => $text) {
            if ($language !== 'default' && $text === ($defaultLabels[$key] ?? null)) {
                // Fallback value, not a translation
                continue;
            }
            $parsedLabels[$key] = [['source' => $defaultLabels[$key] ?? $text, 'target' => $text]];
        }
        return [$language => $parsedLabels];
    }
}
