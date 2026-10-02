<?php

declare(strict_types=1);

use SourceBroker\Translatr\Backend\FormDataProvider\LabelRowInitializeNew;
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew;

defined('TYPO3') || die('Access denied.');

call_user_func(
    function () {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][LabelRowInitializeNew::class] = [
            'depends' => [
                DatabaseRowInitializeNew::class,
            ],
        ];
    }
);
