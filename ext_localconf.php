<?php

use TYPO3\CMS\Core\Core\Environment;

defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function () {
        if (file_exists(Environment::getPublicPath() . '/uploads/tx_translatr/locallangOverrideLoader.php')) {
            require_once(Environment::getPublicPath() . '/uploads/tx_translatr/locallangOverrideLoader.php');
        } else {
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['connectToDB']['transltr']
                = \SourceBroker\Translatr\Hooks\LocallangXMLOverride::class . '->initialize';
        }

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][]
            =  \SourceBroker\Translatr\Service\CacheCleaner::class . '->flushCache';

        if (TYPO3_MODE !== 'FE') {
            // Used to remove 'save and new'. Can be removed when inline editing will be done.
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Backend\Template\Components\ButtonBar']['getButtonsHook'][]
                = \SourceBroker\Translatr\Hooks\ButtonBarHook::class . '->modify';

            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['translatr']
                = \SourceBroker\Translatr\Hooks\TceMain::class;

            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
                '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:transltr/Configuration/TsConfig/Page/tx_translatr.tsconfig">'
            );
        }

        if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version) < 8007000) {
            $GLOBALS['TYPO3_CONF_VARS']['EXT']['EXTCONF']['translatr']['database'] = \SourceBroker\Translatr\Database\Database76::class;
        } else {
            $GLOBALS['TYPO3_CONF_VARS']['EXT']['EXTCONF']['translatr']['database'] = \SourceBroker\Translatr\Database\Database87::class;
        }

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1587914575905] = [
            'nodeName' => 'fieldHidden',
            'priority' => 40,
            'class' => SourceBroker\Translatr\Form\Element\TcaFieldHidden::class,
        ];
    }
);
