<?php

namespace SourceBroker\Translatr\Domain\Model\Dto;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class EmConfiguration
 *
 * @package SourceBroker\Translatr\Domain\Model\Dto
 */
class EmConfiguration
{

    /**
     * Fill the properties properly
     *
     * @param array $configuration em configuration
     */
    public function __construct(array $configuration)
    {
        foreach ($configuration as $key => $value) {
            if (property_exists(__CLASS__, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * @var string
     */
    protected $customPlugins = '';

    /**
     * @var string
     */
    protected $defaultLanguageIsoCode = '';

    /**
     * Returns array of custom plugins with elements `label` (user friendly plugin name) and `plugin` (plugin key).
     *
     * @return array
     */
    public function getCustomPlugins()
    {
        return array_filter(
            array_map(
                function ($pluginData) {
                    // change plugins from string like Label|pluginKey to arrays with `label` and `plugin` elements
                    $pluginDataArray = GeneralUtility::trimExplode('|', $pluginData);

                    return [
                        'label' => isset($pluginDataArray[0]) ? $pluginDataArray[0] : null,
                        'plugin' => isset($pluginDataArray[1]) ? $pluginDataArray[1] : null,
                    ];
                },
                preg_split("/\\r\\n|\\r|\\n/", $this->customPlugins)
            ),
            function ($pluginDataArray) {
                // remove from result array items with empty `plugin` name
                return !!$pluginDataArray['plugin'];
            }
        );
    }

    /**
     * @param string $customPlugins
     */
    public function setCustomPlugins($customPlugins)
    {
        $this->customPlugins = $customPlugins;
    }

    /**
     * @return string
     */
    public function getDefaultLanguageIsoCode()
    {
        return $this->defaultLanguageIsoCode;
    }

    /**
     * @param string $defaultLanguageIsoCode
     */
    public function setDefaultLanguageIsoCode($defaultLanguageIsoCode)
    {
        $this->defaultLanguageIsoCode = $defaultLanguageIsoCode;
    }
}
