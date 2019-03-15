<?php

namespace SourceBroker\Translatr\Utility;

use SourceBroker\Translatr\Configuration\Configurator;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ExtensionsUtility
 *
 */
class ExtensionsUtility
{

    /**
     * @return array
     */
    public static function getExtensionsWithMetaData()
    {
        $config = GeneralUtility::makeInstance(Configurator::class);
        $extensions = $config->getOption('extensions');
        $allExtensions = ExtensionManagementUtility::getLoadedExtensionListArray();
        return array_map(function ($extKey) {
            return MiscUtility::getExtensionMetaData($extKey);
        }, count($extensions) ? array_intersect($extensions, $allExtensions) : $allExtensions);
    }
}
