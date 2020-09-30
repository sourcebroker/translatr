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
        $returnValue = null;
        if ($config['fieldName'] === 'ukey' && empty($value)) {
            $returnValue = '<p style="color: #f00;">Ukey value couldn\'t be determined. Contact your administrator.</p>';
        }

        if ($config['fieldName'] === 'modify' && empty($value)) {
            $returnValue = '<p>No</p>';
        }

        if ($config['fieldName'] === 'tags' && empty($value)) {
            $returnValue = $this->prepareInput('', 'No tags defined', $config['elementBaseName']);
        }

        if (!$returnValue) {
            if (empty($value)) {
                $returnValue = '<p></p>';
            } else {
                $displayValue = $value;
                if (is_numeric($value)) {
                    $displayValue = $value === 0 ? 'No' : 'Yes';
                }
                $returnValue = $this->prepareInput($value, $displayValue, $config['elementBaseName']);
            }
        }
        $result = $this->initializeResultArray();
        $result['html'] = $returnValue;

        return $result;
    }

    protected function prepareInput(string $value, string $displayValue, string $baseName): string
    {
        return <<<HTML
<input type="hidden" value="{$value}" name="data{$baseName}" />
<p>{$displayValue}</p>
HTML;
    }
}
