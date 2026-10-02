<?php

namespace SourceBroker\Translatr\ViewHelpers\Be;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class GetChildlLangViewHelper extends AbstractViewHelper
{
    public const TABLE = 'tx_translatr_domain_model_label';
    public const MODULE_NAME = 'translatr';

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
            'string',
            'Language.',
            false
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function render(): ?array
    {
        $label = $this->arguments['label'];
        $language = $this->arguments['language'];
        return $label['language_childs'][$language] ?? null;
    }
}
