<?php

return [
    'frontend' => [
        'sourcebroker/translator/init' => [
            'target' => \SourceBroker\Translatr\Middleware\GenerateLanguageFiles::class,
            'after' => [
                'typo3/cms-frontend/site',
            ],
            'before' => [
                'typo3/cms-frontend/maintenance-mode',
            ],
        ],
    ],
];
