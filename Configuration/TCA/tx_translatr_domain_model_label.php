<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:translatr/Resources/Private/Language/locallang_db.xlf:tx_translatr_domain_model_label',
        'label' => 'text',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'dividers2tabs' => true,
        'versioningWS' => 2,
        'versioning_followPages' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'searchFields' => 'extension,ukey,text,description,',
        'iconfile' => 'EXT:translatr/Resources/Public/Icons/tx_translatr_domain_model_label.gif',
        'hideTable' => true,
        'rootLevel' => 1,
        'security' => [
            'ignoreRootLevelRestriction' => true,
        ],
    ],
    'interface' => [
        'showRecordFieldList' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, extension, ukey, text, description, ll_file',
    ],
    'types' => [
        '1' => ['showitem' => 'hidden;;1, language, extension, ll_file, ukey, text, description, --div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access, starttime, endtime'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
    'columns' => [

        'sys_language_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],

        't3ver_label' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.versionLabel',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
            ],
        ],

        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
            ],
        ],
        'starttime' => [
            'exclude' => 1,
            'l10n_mode' => 'mergeIfNotBlank',
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'input',
                'size' => 13,
                'max' => 20,
                'eval' => 'datetime',
                'checkbox' => 0,
                'default' => 0,
                'range' => [
                    'lower' => mktime(0, 0, 0, date('m'), date('d'), date('Y')),
                ],
            ],
        ],
        'endtime' => [
            'exclude' => 1,
            'l10n_mode' => 'mergeIfNotBlank',
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'input',
                'size' => 13,
                'max' => 20,
                'eval' => 'datetime',
                'checkbox' => 0,
                'default' => 0,
                'range' => [
                    'lower' => mktime(0, 0, 0, date('m'), date('d'), date('Y')),
                ],
            ],
        ],

        'extension' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:translatr/Resources/Private/Language/locallang_db.xlf:tx_translatr_domain_model_label.extension',
            'config' => [
                'readOnly' => true,
                'type' => 'select',
                'renderType' => 'selectSingle',
                'itemsProcFunc' => \SourceBroker\Translatr\UserFunc\ExtensionItemsProcFunc::class.'->getItems',
                'size' => 1,
                'maxitems' => 1,
                'eval' => 'required',
            ],
        ],
        'ukey' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:translatr/Resources/Private/Language/locallang_db.xlf:tx_translatr_domain_model_label.ukey',
            'config' => [
                'readOnly' => true,
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required',
            ],
        ],
        'text' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:translatr/Resources/Private/Language/locallang_db.xlf:tx_translatr_domain_model_label.text',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
            ],
        ],
        'description' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:translatr/Resources/Private/Language/locallang_db.xlf:tx_translatr_domain_model_label.description',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 2,
                'eval' => 'trim',
            ],
        ],
        'll_file' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:translatr/Resources/Private/Language/locallang_db.xlf:tx_translatr_domain_model_label.ll_file',
            'config' => [
                'readOnly' => true,
                'type' => 'input',
            ],
        ],
        'language' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:translatr/Resources/Private/Language/locallang_db.xlf:tx_translatr_domain_model_label.language',
            'config' => [
                'readOnly' => true,
                'type' => 'select',
                'itemsProcFunc' => \SourceBroker\Translatr\UserFunc\LanguageItemsProcFunc::class.'->getItems',
                'items' => [
                    ['', ''],
                ]
            ],
        ]

    ],
];