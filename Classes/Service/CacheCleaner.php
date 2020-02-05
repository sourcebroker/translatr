<?php
declare(strict_types=1);

namespace SourceBroker\Translatr\Service;

use SourceBroker\Translatr\Utility\FileUtility;
use SourceBroker\Translatr\Utility\MiscUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class CacheCleaner
 * @package SourceBroker\Translatr\Service
 */
class CacheCleaner extends BaseService
{

    /**
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * CacheCleaner constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->cacheManager = $this->objectManager->get(CacheManager::class);
    }

    /**
     * @return null|HtmlResponse
     */
    public function flushCache(): ?HtmlResponse
    {
        $tempPath = FileUtility::getTempFolderPath();
        $tempPathRenamed = $tempPath . time();
        rename($tempPath, $tempPathRenamed);
        GeneralUtility::rmdir($tempPathRenamed, true);
        try {
            $cacheFrontend = $this->cacheManager->getCache('l10n');
            $cacheFrontend->flush();
        } catch (NoSuchCacheException $e) {
        }

        return MiscUtility::isTypo39up() ? new HtmlResponse('') : null;
    }
}
