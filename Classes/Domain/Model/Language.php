<?php

namespace SourceBroker\Translatr\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Language extends AbstractEntity
{
    protected string $title;

    protected string $flag;

    protected string $isoCode;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getFlag(): string
    {
        return $this->flag;
    }

    public function setFlag(string $flag): void
    {
        $this->flag = $flag;
    }

    public function getIsoCode(): string
    {
        return $this->isoCode;
    }

    public function setIsoCode(string $isoCode): void
    {
        $this->isoCode = $isoCode;
    }
}
