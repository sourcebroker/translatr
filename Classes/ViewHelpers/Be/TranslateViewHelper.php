<?php

namespace SourceBroker\Translatr\ViewHelpers\Be;

use SourceBroker\Translatr\Utility\LanguageUtility;

class TranslateViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{
    public function initializeArguments()
    {
        $this->registerArgument('llFile', 'string', 'Path to the locallang file', true);
        $this->registerArgument('language', 'string', 'Translation target language', true);
        $this->registerArgument('key', 'string', 'Label key', true);
    }

    /**
     * We don't need the cache of parse files, because it's done on the parser factory already
     * @return string
     */
    public function render()
    {
        /** @var string $language */
        $language = $this->arguments['language'];
        /** @var string $llFile */
        $llFile = $this->arguments['llFile'];
        /** @var string $key */
        $key = $this->arguments['key'];

        $parsedLabels = LanguageUtility::parseLanguageLabels($llFile, $language);
        return $parsedLabels[$language][$key][0]['target'] ?: '';
    }

    /**
     * @return \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
