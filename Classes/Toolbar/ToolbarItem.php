<?php

namespace SourceBroker\Translatr\Toolbar;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use SourceBroker\Translatr\Service\CacheCleaner;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Core\Http\HtmlResponse;

/**
 * Prepares additional flush cache entry.
 *
 */
class ToolbarItem implements \TYPO3\CMS\Backend\Toolbar\ClearCacheActionsHookInterface
{
    public static $itemKey = 'flushLanguageCache';

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Adds the flush language cache menu item.
     *
     * @param array $cacheActions Array of CacheMenuItems
     * @param array $optionValues Array of AccessConfigurations-identifiers (typically used by userTS with options.clearCache.identifier)
     * @return void
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    public function manipulateCacheActions(&$cacheActions, &$optionValues)
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        if ($this->getBackendUser()->isAdmin() || $this->getBackendUser()->getTSConfigVal('tx_translatr.clearCache.language')) {
            $href = (string)GeneralUtility::makeInstance(UriBuilder::class)
                ->buildUriFromRoute('translatr_toolbaritem_flushcache', []);
            if (VersionNumberUtility::convertVersionNumberToInteger(TYPO3_branch) >
                VersionNumberUtility::convertVersionNumberToInteger('8.0.0')
            ) {
                $cacheActions[] = [
                    'id' => self::$itemKey,
                    'title' => 'LLL:EXT:translatr/Resources/Private/Language/locallang.xlf:flushLanguageCache',
                    'description' => 'LLL:EXT:translatr/Resources/Private/Language/locallang.xlf:flushLanguageCacheDescription',
                    'href' => $href,
                    'iconIdentifier' => 'actions-system-cache-clear-impact-medium'
                ];
            } else {
                $cacheActions[] = [
                    'id' => self::$itemKey,
                    'title' => $this->getLanguageService()->sL('LLL:EXT:translatr/Resources/Private/Language/locallang.xlf:flushLanguageCache'),
                    'href' => $href,
                    'icon' => $this->iconFactory->getIcon(
                        'actions-system-cache-clear-impact-medium',
                        Icon::SIZE_SMALL
                    )->render()
                ];
            }
            $optionValues[] = self::$itemKey;
        }
    }

    /**
     * @return HtmlResponse|null
     */
    public function flushCache(): ?HtmlResponse
    {
        $cache = GeneralUtility::makeInstance(CacheCleaner::class);

        return $cache->flushCache();
    }

    /**
     * Wrapper around the global BE user object.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Wrapper around the global language object.
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
