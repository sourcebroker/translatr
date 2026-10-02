<?php

$config = \TYPO3\CodingStandards\CsFixerConfig::create();
$config->setCacheFile(__DIR__ . '/.Build/.cache/php-cs-fixer.cache');
$config->getFinder()
    ->in(__DIR__)
    ->exclude(['.Build', '.ddev', '.test', 'Documentation'])
;

return $config;
