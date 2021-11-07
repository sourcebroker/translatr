<?php

namespace SourceBroker\Translatr\ViewHelpers\Be;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentValueException;
use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;

/**
 * Class ActionLinkViewHelper
 *
 */
class GetChildlLangViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{
    const TABLE = 'tx_translatr_domain_model_label';
    const MODULE_NAME = 'web_TranslatrTranslate';

    /**
     * @return void
     * @throws \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
     *
     */
    public function initializeArguments()
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

    /**
     * @return array|nill
     * @throws InvalidArgumentValueException
     *
     */
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
