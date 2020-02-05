<?php

namespace SourceBroker\Translatr\Database;

use SourceBroker\Translatr\Domain\Model\Dto\BeLabelDemand;
use SourceBroker\Translatr\Utility\ArrayUtility;

class Database76 implements Database
{
    public function update($table, array $set, array $condition)
    {
        $setArray = [];
        foreach ($set as $field => $value) {
            $setArray[] = sprintf('%s=%s', $field, $value);
        }

        $this->getDatabaseConnection()->exec_UPDATEquery(
            'tx_translatr_domain_model_label',
            implode(',', $setArray),
            $set
        );
    }

    public function getRootPage()
    {
        return $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            'uid',
            'pages',
            'pid=0 AND deleted=0'
        );
    }

    public function findDemandedForBe(BeLabelDemand $demand)
    {
        if (!$demand->isValid()) {
            return [];
        }

        $extensionNameForSql = $this->getDatabaseConnection()->fullQuoteStr(
            $demand->getExtension(),
            'tx_translatr_domain_model_label'
        );

        $languageListForSql = implode(
            ', ',
            $this->getDatabaseConnection()->fullQuoteArray($demand->getLanguages() ?: ['default'],
                'tx_translatr_domain_model_label')
        );

        $query = <<<SQL
/* select labels from default language */
(
SELECT 
  label.uid,
  label.language,
  label.ukey,
  0 AS parent_uid,
  label.text,
  label.ll_file,
  label.tags,
  label.extension
FROM tx_translatr_domain_model_label AS label
WHERE label.language = "default" 
  AND label.deleted = 0
  AND label.extension = {$extensionNameForSql}
) UNION (
/* select labels for specified languages */ 
SELECT  
  label.uid,
  label.language,
  label.ukey,
  parent.uid AS parent_uid,
  label.text,
  label.ll_file
FROM tx_translatr_domain_model_label AS label 
  LEFT JOIN tx_translatr_domain_model_label AS parent
    ON (parent.language = "default" AND parent.ukey = label.ukey AND parent.ll_file = label.ll_file)
WHERE label.language IN ({$languageListForSql})  
  AND label.deleted = 0
  AND parent.deleted = 0
  AND parent.extension = {$extensionNameForSql}
);
SQL;

        // sql_query()->fetch_all() is still not supported on all hostings
        $result = $this->getDatabaseConnection()->sql_query($query);
        $resultAssoc = [];
        while ($row = $result->fetch_assoc()) {
            $resultAssoc[] = $row;
        }
        $results = ArrayUtility::combineWithSubarrayFieldAsKey(
            $resultAssoc,
            'uid'
        );

        $processedResults = [];

        foreach ($results as &$result) {
            $uid = (int)$result['uid'];
            $parentUid = (int)$result['parent_uid'];
            $language = $result['language'];

            if ($language === 'default') {
                // record in default language are treated as parents
                $processedResults[$uid] = $result;
                $processedResults[$uid]['language_childs'] = [];
            } elseif ($parentUid > 0) {
                // add as a child to parent record
                $processedResults[$parentUid]['language_childs'][$language]
                    = $result;
            }
        }

        return $processedResults;
    }

    public function getLabelsByLocallangFile($locallangFile)
    {
        $escapedLocallangFile = $this->getDatabaseConnection()->fullQuoteStr($locallangFile,
            'tx_translatr_domain_model_label');
        return (array)$this->getDatabaseConnection()->exec_SELECTgetRows(
            'label.text,
                label.ukey AS ukey,
                label.extension AS extension,
                label.language AS isocode',
            'tx_translatr_domain_model_label AS label' .
            'label.deleted = 0
                AND label.hidden = 0
                AND (
                    label.ll_file = ' . $escapedLocallangFile . ')'
        );
    }

    public function getLocallanfFiles()
    {
        return (array)$this->getDatabaseConnection()->exec_SELECTgetRows(
            'DISTINCT tx_translatr_domain_model_label.ll_file, language',
            'tx_translatr_domain_model_label',
            'tx_translatr_domain_model_label.deleted = 0'
        );
    }

    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
