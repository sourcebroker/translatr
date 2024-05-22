<?php
return [
    \SourceBroker\Translatr\Domain\Model\Language::class => [
        'tableName' => 'sys_language',
        'properties' => ['isoCode' => ['fieldName' => 'language_isocode']],
    ],
];
