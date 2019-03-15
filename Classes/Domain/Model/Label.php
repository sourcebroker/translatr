<?php

namespace SourceBroker\Translatr\Domain\Model;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2017
 *
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

/**
 * Label
 */
class Label extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * Extension from which the labels comes from
     *
     * @var string
     */
    protected $extension = '';

    /**
     * Unique key of the translation
     *
     * @var string
     */
    protected $ukey = '';

    /**
     * Translated label
     *
     * @var string
     */
    protected $text = '';

    /**
     * description
     *
     * @var string
     */
    protected $description = '';

    /**
     * @var string
     */
    protected $language;

    /**
     * @var string
     */
    protected $llFile = '';

    /**
     * Returns the extension
     *
     * @return string $extension
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * Sets the extension
     *
     * @param string $extension
     *
     * @return void
     */
    public function setExtension($extension)
    {
        $this->extension = $extension;
    }

    /**
     * Returns the ukey
     *
     * @return string $ukey
     */
    public function getUkey()
    {
        return $this->ukey;
    }

    /**
     * Sets the ukey
     *
     * @param string $ukey
     *
     * @return void
     */
    public function setUkey($ukey)
    {
        $this->ukey = $ukey;
    }

    /**
     * Returns the text
     *
     * @return string $text
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Sets the text
     *
     * @param string $text
     *
     * @return void
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * Returns the description
     *
     * @return string $description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets the description
     *
     * @param string $description
     *
     * @return void
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getLlFile()
    {
        return $this->llFile;
    }

    /**
     * @param string $llFile
     */
    public function setLlFile($llFile)
    {
        $this->llFile = $llFile;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }
}
