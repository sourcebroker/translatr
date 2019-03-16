<?php

namespace SourceBroker\Translatr\Utility;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

/**
 * Misc utility.
 */
class MiscUtility
{
    /**
     * Returns meta-data for a given extension.
     *
     * @param string $extensionKey
     * @return array
     */
    public static function getExtensionMetaData($extensionKey)
    {
        $_EXTKEY = $extensionKey;
        $EM_CONF = [];
        $extPath = ExtensionManagementUtility::extPath($extensionKey);
        include($extPath . 'ext_emconf.php');

        $release = $EM_CONF[$_EXTKEY]['version'];
        list($major, $minor, $_) = explode('.', $release, 3);
        if (($pos = strpos($minor, '-')) !== false) {
            // $minor ~ '2-dev'
            $minor = substr($minor, 0, $pos);
        }
        $EM_CONF[$_EXTKEY]['version'] = $major . '.' . $minor;
        $EM_CONF[$_EXTKEY]['release'] = $release;
        $EM_CONF[$_EXTKEY]['extensionKey'] = $extensionKey;

        return $EM_CONF[$_EXTKEY];
    }

    /**
     * @return bool
     */
    public static function isTypo39up()
    {
        return VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version) >= 9000000;
    }
}
