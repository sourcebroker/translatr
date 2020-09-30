<?php

defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function () {
        if (TYPO3_MODE === 'BE') {
            \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
                'SourceBroker.translatr',
                'web',
                'translate',
                '',
                [
                    'Label' => 'list',
                ],
                [
                    'access' => 'user,group',
                    'icon' => 'EXT:translatr/Resources/Public/Icons/Extension.svg',
                    'labels' => 'LLL:EXT:translatr/Resources/Private/Language/locallang_translate.xlf',
                    'navigationComponentId' => '',
                    'inheritNavigationComponentFromMainModule' => false
                ]
            );
        }

        $GLOBALS['TBE_STYLES']['skins']['translatr'] = [
            'name' => 'translatr',
            'stylesheetDirectories' => [
                'select2' => 'EXT:translatr/Resources/Public/JavaScript/jquery.select2/dist/css/',
                'css' => 'EXT:translatr/Resources/Public/Css/'
            ],
        ];

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
            'tx_translatr_domain_model_label',
            'EXT:translatr/Resources/Private/Language/locallang_csh_tx_translatr_domain_model_label.xlf'
        );
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_translatr_domain_model_label');

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'][SourceBroker\Translatr\Backend\FormDataProvider\LabelRowInitializeNew::class] = [
            'depends' => [
                \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew::class,
            ],
        ];
    }
);
