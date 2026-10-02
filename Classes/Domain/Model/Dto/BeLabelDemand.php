<?php

declare(strict_types=1);

namespace SourceBroker\Translatr\Domain\Model\Dto;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class BeLabelDemand extends AbstractEntity
{
    protected ?string $extension = '';

    /**
     * @var array<int|string, string>|null
     */
    protected ?array $languages = null;

    /**
     * @var array<int|string, string>|null
     */
    protected ?array $keys = null;

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function setExtension(?string $extension): void
    {
        $this->extension = $extension;
    }

    public function isValid(): bool
    {
        return !empty($this->extension);
    }

    /**
     * @return array<int|string, string>|null
     */
    public function getLanguages(): ?array
    {
        return $this->languages;
    }

    /**
     * @param array<int|string, string>|null $languages
     */
    public function setLanguages(?array $languages): void
    {
        $this->languages = $languages;
    }

    /**
     * @return array<int|string, string>|null
     */
    public function getKeys(): ?array
    {
        return $this->keys;
    }

    /**
     * @param array<int|string, string> $keys
     */
    public function setKeys(array $keys): void
    {
        $this->keys = $keys;
    }
}
