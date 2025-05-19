<?php

namespace SourceBroker\Translatr\Backend\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LabelRowInitializeNew implements FormDataProviderInterface
{
    private array $data = [];

    public function addData(array $data): array
    {
        $this->setData($data);

        if (!$this->isTranslateLabelTable()) {
            return $this->data;
        }

        if ($this->isNewRecord()) {
            $this->setDefaultDatabaseRowData();
        }

        return $this->data;
    }

    private function setData(array $data)
    {
        $this->data = $data;
    }

    private function isNewRecord(): bool
    {
        return 'new' === $this->data['command'];
    }

    private function isTranslateLabelTable(): bool
    {
        return 'tx_translatr_domain_model_label' === $this->data['tableName'];
    }

    private function setDefaultDatabaseRowData(): void
    {
        $defaultTcaData = $this->getDefaultTcaData();
        $defaultTcaData = is_array($defaultTcaData) ? $defaultTcaData : [];

        $this->data['databaseRow'] = array_replace_recursive(
            $this->data['databaseRow'],
            $defaultTcaData
        );
    }

    private function getDefaultTcaData(): mixed
    {
        return $GLOBALS['TYPO3_REQUEST']->getQueryParams()['translatr_tcadefault'] ?? [];
    }
}
