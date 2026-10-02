<?php

declare(strict_types=1);

use SourceBroker\Translatr\Domain\Model\Language;

return [
    Language::class => [
        'tableName' => 'sys_language',
        'properties' => ['isoCode' => ['fieldName' => 'language_isocode']],
    ],
];
