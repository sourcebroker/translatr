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
            return self::getExtensionMetaData($extKey);
        }, count($extensions) ? array_intersect($extensions, $allExtensions) : $allExtensions);
    }

    public static function getExtensionMetaData($extensionKey)
    {
        $_EXTKEY = $extensionKey;
        $EM_CONF = [];
        $extPath = ExtensionManagementUtility::extPath($extensionKey);
        include($extPath . 'ext_emconf.php');

        $release = $EM_CONF[$_EXTKEY]['version'];
        [$major, $minor, $_] = explode('.', $release, 3);
        if (($pos = strpos($minor, '-')) !== false) {
            // $minor ~ '2-dev'
            $minor = substr($minor, 0, $pos);
        }
        $EM_CONF[$_EXTKEY]['version'] = $major . '.' . $minor;
        $EM_CONF[$_EXTKEY]['release'] = $release;
        $EM_CONF[$_EXTKEY]['extensionKey'] = $extensionKey;

        return $EM_CONF[$_EXTKEY];
    }
}
