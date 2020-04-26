<?php

namespace SourceBroker\Translatr\Form\Element;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;

class TcaFieldHidden extends AbstractFormElement
{
    public function render(): array
    {
        $config = $this->data;
        $value = $config['databaseRow'][$config['fieldName']];

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
<input type="hidden" value="{$value}" name="data{$config['elementBaseName']}" />
<p>{$displayValue}</p>
HTML;
        }
        $result = $this->initializeResultArray();
        $result['html'] = $returnValue;
        return $result;
    }
}
