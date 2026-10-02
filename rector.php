<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\PostRector\Rector\NameImportingPostRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;
use Rector\ValueObject\PhpVersion;
use Ssch\TYPO3Rector\CodeQuality\General\GeneralUtilityMakeInstanceToConstructorPropertyRector;
use Ssch\TYPO3Rector\Configuration\Typo3Option;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;
use Ssch\TYPO3Rector\Set\Typo3SetList;

// Targets the lowest supported versions (PHP 8.2, TYPO3 13). The code supports TYPO3 13 and 14 at once,
// so TYPO3 14 migrations and RemoveTypo3VersionChecksRector are not used.
return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/Classes',
        __DIR__ . '/Configuration',
        __DIR__ . '/ext_emconf.php',
        __DIR__ . '/ext_localconf.php',
        __DIR__ . '/ext_tables.php',
    ])
    ->withCache(__DIR__ . '/.Build/.cache/rector')
    ->withPhpVersion(PhpVersion::PHP_82)
    ->withSets([
        SetList::CODE_QUALITY,
        LevelSetList::UP_TO_PHP_82,
        Typo3SetList::CODE_QUALITY,
        Typo3SetList::GENERAL,
        Typo3LevelSetList::UP_TO_TYPO3_13,
    ])
    ->withPHPStanConfigs([Typo3Option::PHPSTAN_FOR_RECTOR_PATH])
    ->withImportNames(true, true, false, true)
    ->withRules([
        AddVoidReturnTypeWhereNoReturnRector::class,
    ])
    ->withSkip([
        // The services are private and instantiated with GeneralUtility::makeInstance() or "new"
        // (hooks, middleware), so constructor arguments would not be injected
        GeneralUtilityMakeInstanceToConstructorPropertyRector::class,
        NameImportingPostRector::class => [
            'ClassAliasMap.php',
        ],
        // TER does not support strict type declaration in ext_emconf.php files
        SafeDeclareStrictTypesRector::class => [
            '*/ext_emconf.php',
        ],
    ])
;
