<?php

defined('TYPO3') || die('Access denied.');

call_user_func(
    function () {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][SourceBroker\Translatr\Backend\FormDataProvider\LabelRowInitializeNew::class] = [
            'depends' => [
                \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class,
            ],
        ];
    }
);

