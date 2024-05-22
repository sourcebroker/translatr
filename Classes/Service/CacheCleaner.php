<?php

declare(strict_types=1);

namespace SourceBroker\Translatr\Service;

use SourceBroker\Translatr\Utility\FileUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

class CacheCleaner extends BaseService
{

    protected $cacheManager;

    public function __construct()
    {
        parent::__construct();
        $this->cacheManager = GeneralUtility::makeInstance(CacheManager::class);
    }

    public function flushCache(): void
    {
        $directory = FileUtility::getTempFolderPath();
        if (is_link($directory)) {
            // Avoid attempting to rename the symlink see #87367
            $directory = realpath($directory);
        }
        if (is_dir($directory)) {
            $temporaryDirectory = rtrim($directory, '/') . '.' . StringUtility::getUniqueId('remove');
            if (rename($directory, $temporaryDirectory)) {
                GeneralUtility::makeInstance(OpcodeCacheService::class)->clearAllActive($directory);
                GeneralUtility::mkdir($directory);
                clearstatcache();
                GeneralUtility::rmdir($temporaryDirectory, true);
            }
        }
        try {
            $cacheFrontend = $this->cacheManager->getCache('l10n');
            $cacheFrontend->flush();
        } catch (NoSuchCacheException $e) {
        }
    }
}
