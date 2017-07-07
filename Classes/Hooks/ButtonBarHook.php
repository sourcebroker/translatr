<?php

namespace SourceBroker\Translatr\Hooks;

use SourceBroker\Translatr\Xclass\TranslatrSplitButton;

/**
 * Class ButtonBarHook
 *
 * Use to remove 'save and new' can be removed inline editing will be done.
 *
 * @package SourceBroker\Translatr\Hooks
 */
class ButtonBarHook
{
    /**
     * @param array $params
     * @return array
     */
    public function modify(array $params)
    {
        if (empty($params['buttons']) || !isset($params['buttons']['left'])) {
            return $params['buttons'];
        }
        foreach ($params['buttons']['left'] as &$items) {
            foreach ($items as &$button) {
                if ($button instanceof TranslatrSplitButton) {
                    $options = $button->getOptionButtons();

                    foreach ($options as $optionKey => $option) {
                        if ($option->getName() == '_savedoknew') {
                            unset($options[$optionKey]);
                        }
                    }

                    $changedSplitButton = [
                        'primary' => $button->getPrimaryButton(),
                        'options' => $options
                    ];

                    $button->setItems($changedSplitButton);
                }
            }
        }

        return $params['buttons'];
    }
}
