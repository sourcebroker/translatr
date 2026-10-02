<?php

declare(strict_types=1);

namespace SourceBroker\Translatr\Service;

use SourceBroker\Translatr\Utility\FileUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

class CacheCleaner
{
    protected CacheManager $cacheManager;

    public function __construct()
    {
        $this->cacheManager = GeneralUtility::makeInstance(CacheManager::class);
    }

    /**
     * Hook "clearCachePostProc" of DataHandler. It is called for each record of any table whose cache is cleared,
     * e.g. for every record saved by an import, so the generated files are flushed only for the "all" and "system"
     * cache commands and for the labels of translatr.
     *
     * @param array<string, mixed> $params
     */
    public function clearCachePostProc(array $params): void
    {
        if (in_array(strtolower((string)($params['cacheCmd'] ?? '')), ['all', 'system'], true)
            || ($params['table'] ?? null) === 'tx_translatr_domain_model_label'
        ) {
            $this->flushCache();
        }
    }

    public function flushCache(): void
    {
        // Same lock as GenerateLanguageFiles, so the folder is not removed while the files are being generated
        $locker = GeneralUtility::makeInstance(Locker::class);
        $locker->acquire();
        try {
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
        } finally {
            $locker->release();
        }
        try {
            $cacheFrontend = $this->cacheManager->getCache('l10n');
            $cacheFrontend->flush();
        } catch (NoSuchCacheException) {
        }
    }
}
