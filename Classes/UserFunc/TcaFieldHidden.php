<?php

namespace SourceBroker\Translatr\UserFunc;

/**
 * Class LanguageItemsProcFunc
 *
 * @package SourceBroker\Translatr\UserFunc
 */
class TcaFieldHidden
{

    /**
     * @return string
     */
    public function display(&$config)
    {
        $displayValue = $config['itemFormElValue'];

        if (is_array($displayValue)) {
            $displayValue = implode(', ', $displayValue);
        }

        return <<<HTML
<input type="hidden" value="{$config['itemFormElValue']}" name="{$config['itemFormElName']}" />
<p>{$displayValue}</p>
HTML;
    }
}