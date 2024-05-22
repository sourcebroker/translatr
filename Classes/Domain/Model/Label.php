<?php

namespace SourceBroker\Translatr\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Label extends AbstractEntity
{

    /**
     * Extension from which the labels comes from
     */
    protected string $extension = '';

    /**
     * Unique key of the translation
     */
    protected string $ukey = '';

    /**
     * Translated label
     */
    protected string $text = '';

    protected string $description = '';

    protected string $tags = '';

    protected string $language = '';

    protected string $llFile = '';

    protected string $llFileIndex = '';

    protected ?int $modify = null;

    /**
     * Returns the extension
     */
    public function getExtension(): string
    {
        return $this->extension;
    }

    public function setExtension(string $extension): void
    {
        $this->extension = $extension;
    }

    public function getUkey(): string
    {
        return $this->ukey;
    }

    public function setUkey(string $ukey): void
    {
        $this->ukey = $ukey;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text)
    {
        $this->text = $text;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    public function getLlFile(): string
    {
        return $this->llFile;
    }

    public function setLlFile(string $llFile)
    {
        $this->llFile = $llFile;
    }

    public function getLlFileIndex(): string
    {
        return $this->llFileIndex;
    }

    public function setLlFileIndex(string $llFileIndex)
    {
        $this->llFileIndex = $llFileIndex;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language)
    {
        $this->language = $language;
    }

    public function getTags(): string
    {
        return $this->tags;
    }

    public function setTags(string $tags)
    {
        $this->tags = $tags;
    }

    public function getModify(): ?int
    {
        return $this->modify;
    }

    public function setModify(?int $modify)
    {
        $this->modify = $modify;
    }
}
