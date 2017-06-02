<?php
/** @var string $_EXTKEY */

defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function ($extKey) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['connectToDB'][$extKey]
            = \SourceBroker\Translatr\Hooks\LocallangXMLOverride::class
            .'->initialize';

//        if (!\TYPO3\CMS\Core\Utility\GeneralUtility::_GET('eID')) {
//            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['includeStaticTypoScriptSourcesAtEnd'][$extKey] =
//                'SourceBroker\Translatr\Hooks\TypoScriptTranslate->loadTranslations';
//        }
    },
    $_EXTKEY
);
