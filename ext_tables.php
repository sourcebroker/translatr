<?php

defined('TYPO3') || die('Access denied.');

call_user_func(
    function () {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
            'tx_translatr_domain_model_label',
            'EXT:translatr/Resources/Private/Language/locallang_csh_tx_translatr_domain_model_label.xlf'
        );
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][SourceBroker\Translatr\Backend\FormDataProvider\LabelRowInitializeNew::class] = [
            'depends' => [
                \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class,
            ],
        ];
    }
);

