<?php

namespace SourceBroker\Translatr\Backend\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class LabelRowInitializeNew
 *
 */
class LabelRowInitializeNew implements FormDataProviderInterface
{
    /**
     * @var array
     */
    private $data = [];

    /**
     * @param array $data
     *
     * @return array
     */
    public function addData(array $data)
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
     * @param array $data
     */
    private function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return bool
     */
    private function isNewRecord()
    {
        return 'new' === $this->data['command'];
    }

    /**
     * @return bool
     */
    private function isTranslateLabelTable()
    {
        return 'tx_translatr_domain_model_label' === $this->data['tableName'];
    }

    /**
     * @return void
     */
    private function setDefaultDatabaseRowData()
    {
        $defaultTcaData = $this->getDefaultTcaData();
        $defaultTcaData = is_array($defaultTcaData) ? $defaultTcaData : [];

        $this->data['databaseRow'] = array_replace_recursive(
            $this->data['databaseRow'],
            $defaultTcaData
        );
    }

    /**
     * @return mixed
     */
    private function getDefaultTcaData()
    {
        return GeneralUtility::_GP('translatr_tcadefault');
    }
}
