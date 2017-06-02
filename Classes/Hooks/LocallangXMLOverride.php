<?php

namespace SourceBroker\Translatr\Hooks;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class LocallangXMLOverride
 *
 * @package SourceBroker\Translatr\Hooks
 */
class LocallangXMLOverride
{
    /**
     * @var string
     */
    protected $locallangXMLOverrideFilePath = PATH_site.'typo3temp/tx_translatr/locallangXMLOverride.php';

    /**
     *
     */
    public function initialize()
    {
        $this->createLocallangXMLOverrideFileIfNotExists();

        if (!$this->locallangXMLOverrideFileExists()) {
            if ($this->isProductionContext()) {
                // @todo add to TYPO3 logs for production context to not break down the site
            } else {
                throw new \RuntimeException(
                    'Could not create locallang XML Override file in path '.$this->locallangXMLOverrideFilePath.' due to unknown reason.',
                    82347523
                );
            }

            return;
        }

        include $this->locallangXMLOverrideFilePath;
    }

    /**
     * @return bool
     */
    protected function locallangXMLOverrideFileExists()
    {
        return file_exists($this->locallangXMLOverrideFilePath);
    }

    /**
     * @return void
     */
    protected function createLocallangXMLOverrideFileIfNotExists()
    {
        if ($this->locallangXMLOverrideFileExists()) {
            return;
        }

        $this->createLocallangXMLOverrideFile();
    }

    /**
     * @return bool
     */
    protected function isProductionContext()
    {
        return GeneralUtility::getApplicationContext()->isProduction();
    }

    /**
     * @todo
     *
     * @return void
     */
    protected function createLocallangXMLOverrideFile()
    {

    }
}