<?php

namespace SourceBroker\Translatr\ViewHelpers\Be;

use SourceBroker\Translatr\Utility\LanguageUtility;

/**
 * Class TranslateViewHelper
 *
 * @package SourceBroker\Translatr\ViewHelpers\Be
 */
class TranslateViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{

    /**
     * @throws \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
     *
     * @return void
     */
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
        /** @var string $llFile */
        /** @var string $key */
        extract($this->arguments);
        $parsedLabels = LanguageUtility::parseLanguageLabels($llFile, $language);
        return $parsedLabels[$language][$key][0]['target'] ?: '';
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}