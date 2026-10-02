<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Information\Typo3Version;

$tca = [
    'ctrl' => [
        'title' => 'LLL:EXT:translatr/Resources/Private/Language/locallang_db.xlf:tx_translatr_domain_model_label',
        'label' => 'text',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'enablecolumns' => [
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'iconfile' => 'EXT:translatr/Resources/Public/Icons/tx_translatr_domain_model_label.gif',
        'hideTable' => true,
        'rootLevel' => 1,
        'security' => [
            'ignoreWebMountRestriction' => true,
            'ignoreRootLevelRestriction' => true,
        ],
    ],
    'types' => [
        '1' => ['showitem' => 'text, description, --div--;Extra , tags, language, extension, ll_file, ll_file_index, ukey, modify, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access, starttime, endtime'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
    'columns' => [
        'starttime' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'datetime',
                'format' => 'datetime',
                'default' => 0,
                'searchable' => false,
            ],
        ],
        'endtime' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'datetime',
                'format' => 'datetime',
                'default' => 0,
                'searchable' => false,
            ],
        ],
        'extension' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:translatr/Resources/Private/Language/locallang_db.xlf:tx_translatr_domain_model_label.extension',
            'config' => [
                'type' => 'user',
                'renderType' => 'fieldHidden',
                'required' => true,
            ],
        ],
        'ukey' => [
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:translatr/Resources/Private/Language/locallang_db.xlf:tx_translatr_domain_model_label.ukey',
            'config' => [
                'type' => 'user',
                'renderType' => 'fieldHidden',
                'eval' => 'trim',
                'required' => true,
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
                'default' => '',
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
                'default' => '',
            ],
        ],
        'll_file' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:translatr/Resources/Private/Language/locallang_db.xlf:tx_translatr_domain_model_label.ll_file',
            'config' => [
                'type' => 'user',
                'renderType' => 'fieldHidden',
            ],
        ],
        'll_file_index' => [
            'label' => 'LLL:EXT:translatr/Resources/Private/Language/locallang_db.xlf:tx_translatr_domain_model_label.ll_file_index',
            'config' => [
                'type' => 'user',
                'renderType' => 'fieldHidden',
            ],
        ],
        'language' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:translatr/Resources/Private/Language/locallang_db.xlf:tx_translatr_domain_model_label.language',
            'config' => [
                'type' => 'user',
                'renderType' => 'fieldHidden',
            ],
        ],
        'tags' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:translatr/Resources/Private/Language/locallang_db.xlf:tx_translatr_domain_model_label.tags',
            'config' => [
                'type' => 'user',
                'renderType' => 'fieldHidden',
                'default' => '',
            ],
        ],
        'modify' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:translatr/Resources/Private/Language/locallang_db.xlf:tx_translatr_domain_model_label.modify',
            'config' => [
                'type' => 'user',
                'renderType' => 'fieldHidden',
            ],
        ],
    ],
];

// TYPO3 14 no longer evaluates "searchFields", the fields are searchable based on their type
if ((new Typo3Version())->getMajorVersion() < 14) {
    $tca['ctrl']['searchFields'] = 'extension,ukey,text,description';
}

return $tca;
