<?php

namespace SourceBroker\Translatr\Configuration;

use SourceBroker\Translatr\Database\Database;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Configurator
{
    /**
     * @var array<string, mixed>|null
     */
    protected ?array $config = null;

    /**
     * @param array<string, mixed>|null $config
     */
    public function __construct(?array $config = null)
    {
        if ($config !== null) {
            $this->setConfig($config);
        } else {
            $rootPageForTsConfig
                = GeneralUtility::makeInstance(Database::class)->getRootPage();
            $serviceConfig = GeneralUtility::makeInstance(TypoScriptService::class)
                ->convertTypoScriptArrayToPlainArray(BackendUtility::getPagesTSconfig($rootPageForTsConfig));
            if (isset($serviceConfig['tx_translatr'])) {
                $this->setConfig($serviceConfig['tx_translatr']);
            }
        }
    }

    /**
     * @param array<string, mixed>|null $config
     */
    public function setConfig(?array $config): void
    {
        $this->config = $config;
    }

    /**
     * Return option from configuration array with support for nested comma separated notation as "option1.suboption"
     *
     * @param array<string, mixed>|null $overwriteConfig
     * @return array<string, mixed>|string|null
     */
    public function getOption(?string $name = null, ?array $overwriteConfig = null): array|string|null
    {
        $config = null;
        if (is_string($name)) {
            $config = $overwriteConfig ?? $this->config;
            foreach (explode('.', $name) as $piece) {
                if (!is_array($config) || !array_key_exists($piece, $config)) {
                    return null;
                }
                $config = $config[$piece];
            }
        }
        return $config;
    }
}
