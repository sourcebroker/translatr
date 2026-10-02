<?php

declare(strict_types=1);

use SourceBroker\Translatr\Middleware\GenerateLanguageFiles;

return [
    'frontend' => [
        'sourcebroker/translator/init' => [
            'target' => GenerateLanguageFiles::class,
            'after' => [
                'typo3/cms-frontend/site',
            ],
            'before' => [
                'typo3/cms-frontend/maintenance-mode',
            ],
        ],
    ],
];
