<?php
/** @var string $_EXTKEY */

defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function ($extKey) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['connectToDB'][$extKey]
            = \SourceBroker\Translatr\Hooks\LocallangXMLOverride::class
            . '->initialize';
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions']['tranlatr'] = \SourceBroker\Translatr\Toolbar\ToolbarItem::class;

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler (
            'language_cache::flushCache',
            \SourceBroker\Translatr\Toolbar\ToolbarItem::class . '->flushCache'
        );

    },
    $_EXTKEY
);
