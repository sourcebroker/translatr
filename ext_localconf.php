<?php

declare(strict_types=1);

use SourceBroker\Translatr\Form\Element\TcaFieldHidden;
use SourceBroker\Translatr\Hooks\TceMain;
use SourceBroker\Translatr\Service\CacheCleaner;

defined('TYPO3') || die('Access denied.');

call_user_func(
    function () {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][]
            = CacheCleaner::class . '->clearCachePostProc';

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['translatr']
            = TceMain::class;

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1587914575905] = [
            'nodeName' => 'fieldHidden',
            'priority' => 40,
            'class' => TcaFieldHidden::class,
        ];
    }
);
