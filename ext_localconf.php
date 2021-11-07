<?php

defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function () {
        if (TYPO3_MODE !== 'FE') {
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][]
                =  \SourceBroker\Translatr\Service\CacheCleaner::class . '->flushCache';

            // Used to remove 'save and new'. Can be removed when inline editing will be done.
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Backend\Template\Components\ButtonBar']['getButtonsHook'][]
                = \SourceBroker\Translatr\Hooks\ButtonBarHook::class . '->modify';

            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['translatr']
                = \SourceBroker\Translatr\Hooks\TceMain::class;

            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
                '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:translatr/Configuration/TsConfig/Page/tx_translatr.tsconfig">'
            );

            $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1587914575905] = [
                'nodeName' => 'fieldHidden',
                'priority' => 40,
                'class' => SourceBroker\Translatr\Form\Element\TcaFieldHidden::class,
            ];
        }
    }
);
