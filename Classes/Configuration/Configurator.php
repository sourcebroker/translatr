<?php

declare(strict_types=1);

namespace SourceBroker\Translatr\Configuration;

use SourceBroker\Translatr\Database\RootPageProvider;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;

class Configurator
{
    /**
     * @var array<string, mixed>|null
     */
    protected ?array $config = null;
    private bool $loaded = false;

    public function __construct(
        private readonly RootPageProvider $rootPageProvider,
        private readonly TypoScriptService $typoScriptService,
    ) {}

    private function load(): void
    {
        $serviceConfig = $this->typoScriptService->convertTypoScriptArrayToPlainArray(
            BackendUtility::getPagesTSconfig($this->rootPageProvider->getRootPage())
        );
        if (isset($serviceConfig['tx_translatr'])) {
            $this->setConfig($serviceConfig['tx_translatr']);
        }
        $this->loaded = true;
    }

    /**
     * @param array<string, mixed>|null $config
     */
    public function setConfig(?array $config): void
    {
        $this->config = $config;
        $this->loaded = true;
    }

    public function isManualSynchronizationEnabled(): bool
    {
        return $this->getOption('showSyncButton') === '1';
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
            if ($overwriteConfig === null && !$this->loaded) {
                $this->load();
            }
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
