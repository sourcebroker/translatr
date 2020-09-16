<?php

namespace SourceBroker\Translatr\Hooks;

use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;

class ButtonBarHook
{
    public function modify(array $params)
    {
        if (empty($params['buttons']['left'])) {
            return $params['buttons'];
        }
        foreach ($params['buttons']['left'] as $key => &$items) {
            foreach ($items as &$button) {
                if ($button instanceof LinkButton) {
                    if ($button->getClasses() === 't3js-editform-new') {
                        unset($params['buttons']['left'][$key]);
                    }
                }
            }
        }
        return $params['buttons'];
    }
}
