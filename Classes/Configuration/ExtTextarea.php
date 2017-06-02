<?php

namespace SourceBroker\Translatr\Configuration;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Class ExtMultiSelect
 *
 * @package SourceBroker\Translatr\Configuration
 */
class ExtTextarea
{
    /**
     * @var \TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder
     */
    protected $tag;

    /**
     * @var string
     */
    protected $fieldId = null;

    /**
     * constructor of this class
     */
    public function __construct()
    {
        $this->tag = GeneralUtility::makeInstance(TagBuilder::class);
    }

    /**
     * render textarea for extConf
     *
     * @param array $parameter
     *
     * @return string
     */
    public function render(array $parameter = array())
    {
        $this->tag->setTagName('textarea');
        $this->tag->forceClosingTag(true);
        $this->tag->addAttribute('rows', '10');
        $this->tag->addAttribute('cols', '40');
        $this->tag->addAttribute('name', $parameter['fieldName']);
        $this->tag->setContent($parameter['fieldValue']);

        return $this->tag->render();
    }

}