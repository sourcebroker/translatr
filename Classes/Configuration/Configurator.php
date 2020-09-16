<?php

namespace SourceBroker\Translatr\Configuration;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Configurator
 */
class Configurator
{
    /**
     * Configuration of module set as array
     *
     * @var null|array
     */
    protected $config = null;

    public function __construct($config = null)
    {
        if ($config !== null) {
            $this->setConfig($config);
        } else {
            $rootPageForTsConfig = null;
            $rootPageForTsConfigRow
                = GeneralUtility::makeInstance($GLOBALS['TYPO3_CONF_VARS']['EXT']['EXTCONF']['translatr']['database'])->getRootPage();
            if ($rootPageForTsConfigRow !== null) {
                $rootPageForTsConfig = $rootPageForTsConfigRow['uid'];
            }
            $serviceConfig = GeneralUtility::makeInstance(\TYPO3\CMS\Core\TypoScript\TypoScriptService::class)
                ->convertTypoScriptArrayToPlainArray(BackendUtility::getPagesTSconfig($rootPageForTsConfig));
            if (isset($serviceConfig['tx_translatr'])) {
                $this->setConfig($serviceConfig['tx_translatr']);
            }
        }
    }

    /**
     * @param array|null $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * Return option from configuration array with support for nested comma separated notation as "option1.suboption"
     *
     * @param string $name Configuration
     * @param null $overwriteConfig
     * @return array|null|string
     */
    public function getOption($name = null, $overwriteConfig = null)
    {
        $config = null;
        if (is_string($name)) {
            $pieces = explode('.', $name);
            if ($pieces !== false) {
                if ($overwriteConfig === null) {
                    $config = $this->config;
                } else {
                    $config = $overwriteConfig;
                }
                foreach ($pieces as $piece) {
                    if (!is_array($config) || !array_key_exists($piece, $config)) {
                        return null;
                    }
                    $config = $config[$piece];
                }
            }
        }
        return $config;
    }
}
