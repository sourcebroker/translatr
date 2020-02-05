<?php

namespace SourceBroker\Translatr\UserFunc;

/**
 * Class LanguageItemsProcFunc
 *
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

        if (!is_numeric($value) && empty($value)) {
            $returnValue = $config['field'] == 'ukey' ? '<p style="color: #f00;">Ukey value couldn\'t be determined. Contact your administrator.</p>' : '<p></p>';
        } else {
            $displayValue = $value;
            if (is_numeric($value)) {
                $displayValue = $value === 0 ? 'No' : 'Yes';
            }
            $returnValue = <<<HTML
<input type="hidden" value="{$value}" name="{$config['itemFormElName']}" />
<p>{$displayValue}</p>
HTML;
        }

        return $returnValue;
    }
}
