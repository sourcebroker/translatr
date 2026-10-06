<?php

declare(strict_types=1);

namespace SourceBroker\Translatr\Service;

use SourceBroker\Translatr\Utility\FileUtility;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

final readonly class GenerationRetry
{
    public const DELAY = 300;

    public function __construct(private FrontendInterface $cache) {}

    /** @phpstan-impure */
    public function isDeferred(): bool
    {
        return $this->cache->has($this->key());
    }

    public function defer(): void
    {
        $this->cache->set($this->key(), true, [], self::DELAY);
    }

    public function reset(): void
    {
        $this->cache->remove($this->key());
    }

    private function key(): string
    {
        return 'translatr_retry_' . hash('sha256', FileUtility::getTempFolderPath(false));
    }
}
