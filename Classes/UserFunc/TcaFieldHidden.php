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
        $value = $config['itemFormElValue'];

        while (is_array($value)) {
            $value = array_shift($value);
        }

        return <<<HTML
<input type="hidden" value="{$value}" name="{$config['itemFormElName']}" />
<p>{$value}</p>
HTML;
    }
}