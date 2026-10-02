<?php

namespace SourceBroker\Translatr\Backend\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;

class LabelRowInitializeNew implements FormDataProviderInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
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

    /**
     * @param array<string, mixed> $data
     */
    private function setData(array $data): void
    {
        $this->data = $data;
    }

    private function isNewRecord(): bool
    {
        return $this->data['command'] === 'new';
    }

    private function isTranslateLabelTable(): bool
    {
        return $this->data['tableName'] === 'tx_translatr_domain_model_label';
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
