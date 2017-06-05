<?php

namespace SourceBroker\Translatr\Domain\Model\Dto;

/**
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
use SourceBroker\Translatr\Domain\Model\Language;

/**
 * Administration Demand model
 *
 */
class BeLabelDemand extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * @var string
     */
    protected $extension = '';

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\SourceBroker\Translatr\Domain\Model\Language>
     */
    protected $languages = null;

    /**
     * BeLabelDemand constructor.
     */
    public function __construct()
    {
        $this->languages = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    /**
     * @return string
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * @param string $extension
     */
    public function setExtension($extension)
    {
        $this->extension = $extension;
    }

    /**
     * Checks if all required properties are set
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->getExtension();
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\SourceBroker\Translatr\Domain\Model\Language>
     */
    public function getLanguages()
    {
        return $this->languages;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\SourceBroker\Translatr\Domain\Model\Language> $languages
     */
    public function setLanguages($languages)
    {
        $this->languages = $languages;
    }

    /**
     * @return array<int>
     */
    public function getLanguageUids()
    {
        return array_map(function(Language $language) {
            return (int)$language->getUid();
        }, $this->getLanguages()->toArray());
    }

}
