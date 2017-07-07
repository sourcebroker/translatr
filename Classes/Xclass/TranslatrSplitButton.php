<?php

namespace SourceBroker\Translatr\Xclass;

use TYPO3\CMS\Backend\Template\Components\Buttons\InputButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\SplitButton;

/**
 * Xclass SplitButton to add getters and setters
 *
 * Use to remove 'save and new' can be removed inline editing will be done.
 */
class TranslatrSplitButton extends SplitButton
{
    /**
     * @return InputButton
     */
    public function getPrimaryButton()
    {
        return $this->items['primary'];
    }

    /**
     * @return InputButton[]
     */
    public function getOptionButtons()
    {
        return $this->items['options'];
    }

    /**
     * @param array $items
     */
    public function setItems(array $items)
    {
        $this->items = $items;
    }
}
