<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:translatr/Resources/Private/Language/locallang_db.xlf:tx_translatr_domain_model_label',
        'label' => 'text',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'dividers2tabs' => true,
        'delete' => 'deleted',
        'enablecolumns' => [
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'searchFields' => 'extension,ukey,text,description,',
        'iconfile' => 'EXT:translatr/Resources/Public/Icons/tx_translatr_domain_model_label.gif',
        'hideTable' => true,
        'rootLevel' => 1,
        'security' => [
            'ignoreWebMountRestriction' => true,
            'ignoreRootLevelRestriction' => true,
        ],
    ],
    'interface' => [
        'showRecordFieldList' => 'extension, ukey, text, description, ll_file',
    ],
    'types' => [
        '1' => ['showitem' => 'text, description, tags, language, extension, ll_file, ukey, modify, --div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access, starttime, endtime'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
    'columns' => [
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
                'type' => 'user',
                'userFunc' => \SourceBroker\Translatr\UserFunc\TcaFieldHidden::class . '->display',
                'eval' => 'required',
            ],
        ],
        'ukey' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:translatr/Resources/Private/Language/locallang_db.xlf:tx_translatr_domain_model_label.ukey',
            'config' => [
                'type' => 'user',
                'userFunc' => \SourceBroker\Translatr\UserFunc\TcaFieldHidden::class . '->display',
                'eval' => 'trim,required',
            ],
        ],
        'text' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:translatr/Resources/Private/Language/locallang_db.xlf:tx_translatr_domain_model_label.text',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 3,
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
                'type' => 'user',
                'userFunc' => \SourceBroker\Translatr\UserFunc\TcaFieldHidden::class . '->display',
            ],
        ],
        'll_file_index' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'language' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:translatr/Resources/Private/Language/locallang_db.xlf:tx_translatr_domain_model_label.language',
            'config' => [
                'type' => 'user',
                'userFunc' => \SourceBroker\Translatr\UserFunc\TcaFieldHidden::class . '->display',
            ],
        ],
        'tags' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:translatr/Resources/Private/Language/locallang_db.xlf:tx_translatr_domain_model_label.tags',
            'config' => [
                'type' => 'user',
                'userFunc' => \SourceBroker\Translatr\UserFunc\TcaFieldHidden::class . '->display',
            ],
        ],
        'modify' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:translatr/Resources/Private/Language/locallang_db.xlf:tx_translatr_domain_model_label.modify',
            'config' => [
                'type' => 'user',
                'userFunc' => \SourceBroker\Translatr\UserFunc\TcaFieldHidden::class . '->display',
            ],
        ],
    ],
];
