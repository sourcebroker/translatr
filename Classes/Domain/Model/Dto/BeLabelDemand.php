<?php

namespace SourceBroker\Translatr\Domain\Model\Dto;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class BeLabelDemand extends AbstractEntity
{

    protected ?string $extension = '';

    protected ?array $languages = null;

    protected ?array $keys = null;

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function setExtension(?string $extension)
    {
        $this->extension = $extension;
    }

    public function isValid(): bool
    {
        return !empty($this->extension);
    }

    public function getLanguages(): ?array
    {
        return $this->languages;
    }

    public function setLanguages(?array $languages): void
    {
        $this->languages = $languages;
    }

    public function getKeys(): ?array
    {
        return $this->keys;
    }

    public function setKeys(array $keys): void
    {
        $this->keys = $keys;
    }
}
