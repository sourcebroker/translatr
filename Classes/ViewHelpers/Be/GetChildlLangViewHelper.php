<?php

namespace SourceBroker\Translatr\ViewHelpers\Be;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class GetChildlLangViewHelper extends AbstractViewHelper
{
    const TABLE = 'tx_translatr_domain_model_label';
    const MODULE_NAME = 'translatr';

    public function initializeArguments(): void
    {
        $this->registerArgument(
            'label',
            'array',
            'Label on which action should be taken.',
            false
        );
        $this->registerArgument(
            'language',
            'sting',
            'Language.',
            false
        );
    }

    public function render(): ?array
    {
        $label = $this->arguments['label'];
        $language = $this->arguments['language'];
        if (isset($label['language_childs'][$language])) {
            return $label['language_childs'][$language];
        }
        return null;
    }
}
