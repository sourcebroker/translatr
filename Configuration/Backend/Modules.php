<?php

return [
    'translatr' => [
        'parent' => 'tools',
        'position' => ['before' => '*'],
        'access' => 'group,user',
        'iconIdentifier' => 'ext-translatr',
        'labels' => 'LLL:EXT:translatr/Resources/Private/Language/locallang_label.xlf:mlang_tabs_tab',
        'inheritNavigationComponentFromMainModule' => false,
        'extensionName' => 'Translatr',
        'controllerActions' => [
            SourceBroker\Translatr\Controller\LabelController::class => [
                'index',
                'list',
            ],

        ],
    ],
];
