<?php

declare(strict_types=1);

use SourceBroker\Translatr\Backend\FormDataProvider\LabelRowInitializeNew;
use SourceBroker\Translatr\Form\Element\TcaFieldHidden;
use SourceBroker\Translatr\Hooks\TceMain;
use SourceBroker\Translatr\Service\CacheCleaner;
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew;
use TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend;

defined('TYPO3') || die('Access denied.');

call_user_func(
    function () {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['translatr_index'] ??= [];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['translatr_index']['backend'] ??= SimpleFileBackend::class;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['translatr_index']['groups'] ??= ['system'];

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][]
            = CacheCleaner::class . '->clearCachePostProc';

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['translatr']
            = TceMain::class;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['translatr']
            = TceMain::class;

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1587914575905] = [
            'nodeName' => 'fieldHidden',
            'priority' => 40,
            'class' => TcaFieldHidden::class,
        ];

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][LabelRowInitializeNew::class] = [
            'depends' => [
                DatabaseRowInitializeNew::class,
            ],
        ];
    }
);
