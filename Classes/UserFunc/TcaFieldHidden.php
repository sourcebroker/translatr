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
     * @param $config
     * @return string
     */
    public function display(&$config)
    {
        $value = $config['itemFormElValue'];

        while (is_array($value)) {
            $value = array_shift($value);
        }

        if (empty($value)) {
            $returnValue = '<p style="color: #f00;">Ukey value couldn\'t be determined. Contact your administrator.</p>';
        } else {
            $returnValue = <<<HTML
<input type="hidden" value="{$value}" name="{$config['itemFormElName']}" />
<p>{$value}</p>
HTML;
        }

        return $returnValue;
    }
}