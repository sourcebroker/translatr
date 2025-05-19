<?php

defined('TYPO3') || die('Access denied.');

call_user_func(
    function () {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][]
            = \SourceBroker\Translatr\Service\CacheCleaner::class . '->flushCache';

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['translatr']
            = \SourceBroker\Translatr\Hooks\TceMain::class;

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1587914575905] = [
            'nodeName' => 'fieldHidden',
            'priority' => 40,
            'class' => SourceBroker\Translatr\Form\Element\TcaFieldHidden::class,
        ];
    }
);
