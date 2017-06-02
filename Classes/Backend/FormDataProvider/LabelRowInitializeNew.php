<?php

namespace SourceBroker\Translatr\Backend\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class LabelRowInitializeNew
 *
 * @package SourceBroker\Translatr\Backend\FormDataProvider
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
        $this->data['databaseRow'] = array_replace_recursive(
            $this->data['databaseRow'],
            [
                'sys_language_uid' => $this->getDefaultTcaData('sys_language_uid')
                    ?: $this->data['databaseRow']['sys_language_uid'],
                'extension' => $this->getDefaultTcaData('extension')
                    ?: $this->data['databaseRow']['extension'],
            ]
        );
    }

    /**
     * @return mixed
     */
    private function getDefaultTcaData($parameterName)
    {
        $defaultData = GeneralUtility::_GP('translatr_tcadefault');

        return is_array($defaultData) && isset($defaultData[$parameterName])
            ? $defaultData[$parameterName] : null;
    }
}