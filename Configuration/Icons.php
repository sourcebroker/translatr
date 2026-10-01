<?php

use TYPO3\CMS\Core\Information\Typo3Version;

return [
    'ext-translatr' => [
        'provider' => \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        // TYPO3 14 uses monochrome module icons (currentColor + accent), older versions colored tiles
        'source' => (new Typo3Version())->getMajorVersion() >= 14
            ? 'EXT:translatr/Resources/Public/Icons/module-translatr.svg'
            : 'EXT:translatr/Resources/Public/Icons/Extension.svg',
    ],
];
