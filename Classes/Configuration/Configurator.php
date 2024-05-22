<?php

namespace SourceBroker\Translatr\Configuration;

use SourceBroker\Translatr\Database\Database;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Configurator
{
    protected ?array $config = null;

    public function __construct($config = null)
    {
        if ($config !== null) {
            $this->setConfig($config);
        } else {
            $rootPageForTsConfig
                = GeneralUtility::makeInstance(Database::class)->getRootPage();
            $serviceConfig = GeneralUtility::makeInstance(\TYPO3\CMS\Core\TypoScript\TypoScriptService::class)
                ->convertTypoScriptArrayToPlainArray(BackendUtility::getPagesTSconfig($rootPageForTsConfig));
            if (isset($serviceConfig['tx_translatr'])) {
                $this->setConfig($serviceConfig['tx_translatr']);
            }
        }
    }

    public function setConfig(?array $config)
    {
        $this->config = $config;
    }

    /**
     * Return option from configuration array with support for nested comma separated notation as "option1.suboption"
     */
    public function getOption(string $name = null, $overwriteConfig = null): array|null|string
    {
        $config = null;
        if (is_string($name)) {
            $pieces = explode('.', $name);
            if ($pieces !== false) {
                if ($overwriteConfig === null) {
                    $config = $this->config;
                } else {
                    $config = $overwriteConfig;
                }
                foreach ($pieces as $piece) {
                    if (!is_array($config) || !array_key_exists($piece, $config)) {
                        return null;
                    }
                    $config = $config[$piece];
                }
            }
        }
        return $config;
    }
}
