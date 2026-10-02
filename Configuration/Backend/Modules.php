<?php

use SourceBroker\Translatr\Controller\LabelController;
use TYPO3\CMS\Core\Information\Typo3Version;

return [
    'translatr' => [
        // TYPO3 14 renamed the "web" main module to "content" (#107628); parent aliases are not resolved
        'parent' => (new Typo3Version())->getMajorVersion() >= 14 ? 'content' : 'web',
        'position' => ['before' => '*'],
        'access' => 'user',
        'iconIdentifier' => 'ext-translatr',
        'labels' => 'LLL:EXT:translatr/Resources/Private/Language/locallang_label.xlf',
        'inheritNavigationComponentFromMainModule' => false,
        'extensionName' => 'Translatr',
        'controllerActions' => [
            LabelController::class => [
                'index',
                'list',
            ],

        ],
    ],
];
