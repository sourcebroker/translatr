<?php

namespace SourceBroker\Translatr\Domain\Model\Dto;

/**
 * Class EmConfiguration
 *
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
    protected $defaultLanguageIsoCode = '';

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
