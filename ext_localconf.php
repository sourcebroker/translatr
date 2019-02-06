<?php
/** @var string $_EXTKEY */

defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function ($extKey) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['connectToDB'][$extKey]
            = \SourceBroker\Translatr\Hooks\LocallangXMLOverride::class
            . '->initialize';

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions']['translatr'] = \SourceBroker\Translatr\Toolbar\ToolbarItem::class;

        // Used to remove 'save and new'. Can be removed when inline editing will be done.
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Backend\Template\Components\ButtonBar']['getButtonsHook'][]
            = \SourceBroker\Translatr\Hooks\ButtonBarHook::class . '->modify';

        // Used to remove 'save and new'. Can be removed when inline editing will be done.
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\Template\Components\Buttons\SplitButton::class] = array(
            'className' => \SourceBroker\Translatr\Xclass\TranslatrSplitButton::class,
        );

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['translatr']
            = \SourceBroker\Translatr\Hooks\TceMain::class;

        if (TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version) <= 8007999) {
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler(
                'language_cache::flushCache',
                \SourceBroker\Translatr\Toolbar\ToolbarItem::class . '->flushCache'
            );
        }

        if (TYPO3_MODE !== 'FE') {
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
                '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . $extKey . '/Configuration/TsConfig/Page/tx_translatr.tsconfig">'
            );
        }
    },
    $_EXTKEY
);
