<?php

namespace SourceBroker\Translatr\Utility;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Utility class to get the settings from Extension Manager
 *
 */
class EmConfigurationUtility
{

    /**
     * Parses the extension settings.
     *
     * @return \SourceBroker\Translatr\Domain\Model\Dto\EmConfiguration
     * @throws \Exception If the configuration is invalid.
     */
    public static function getSettings()
    {
        $configuration = self::parseSettings();
        require_once ExtensionManagementUtility::extPath('translatr')
            . 'Classes/Domain/Model/Dto/EmConfiguration.php';
        $settings
            = new \SourceBroker\Translatr\Domain\Model\Dto\EmConfiguration($configuration);

        return $settings;
    }

    /**
     * Parse settings and return it as array
     *
     * @return array unserialized extconf settings
     */
    public static function parseSettings()
    {
        $settings
            = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['translatr']);

        if (!is_array($settings)) {
            $settings = [];
        }

        return $settings;
    }
}
