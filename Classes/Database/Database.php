<?php

namespace SourceBroker\Translatr\Database;

use Doctrine\DBAL\ParameterType;
use SourceBroker\Translatr\Domain\Model\Dto\BeLabelDemand;
use SourceBroker\Translatr\Utility\ArrayUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Database implements DatabaseInterface
{
    public function delete($table, array $condition): void
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->delete($table);
        foreach ($condition as $key => $value) {
            $queryBuilder
                ->andWhere(sprintf('%s = :%s', $key, $key))
                ->setParameter($key, $value);
        }
        $queryBuilder->executeStatement();
    }

    public function update($table, array $set, array $condition): void
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder
            ->update($table);

        foreach ($set as $key => $value) {
            $queryBuilder
                ->set($key, $value);
        }

        foreach ($condition as $key => $value) {
            $queryBuilder
                ->andWhere(sprintf('%s = :%s', $key, $key))
                ->setParameter($key, $value);
        }

        $queryBuilder->executeStatement();
    }

    public function getRootPage(): int
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        return $queryBuilder
            ->select('uid')
            ->from('pages')->where($queryBuilder->expr()->eq('pid', 0),
                $queryBuilder->expr()->eq('deleted', 0))->executeQuery()->fetchOne();
    }

    public function findDemandedForBe(BeLabelDemand $demand): array
    {
        if (!$demand->isValid()) {
            return [];
        }
        $keyWhere = '';
        if ($demand->getKeys()) {
            $keyWhere = ' AND label.ukey IN (' . implode(',', $this->wrapArrayByQuote($demand->getKeys())) . ') ';
        }
        $languages = implode(',',
            $this->wrapArrayByQuote($demand->getLanguages() ? $demand->getLanguages() : ['default']));
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
  label.ll_file_index,
  label.tags,
  label.extension,
  label.modify
FROM tx_translatr_domain_model_label AS label
WHERE label.language = "default"
  AND label.extension = ?
  $keyWhere
) UNION (
/* select labels for specified languages */
SELECT
  label.uid,
  label.language,
  label.ukey,
  parent.uid AS parent_uid,
  label.text,
  label.ll_file,
  label.ll_file_index,
  label.tags,
  label.extension,
  label.modify
FROM tx_translatr_domain_model_label AS label
  LEFT JOIN tx_translatr_domain_model_label AS parent
    ON (parent.language = "default" AND parent.ukey = label.ukey AND parent.ll_file = label.ll_file)
WHERE label.language IN ($languages)
  AND parent.extension = ?
);
SQL;
        /** @var Connection $connection */
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName('Default');
        $stmt = $connection->executeQuery(
            $query,
            [
                $demand->getExtension(),
                $demand->getExtension()
            ],
            [
                ParameterType::STRING,
                ParameterType::STRING,
            ]
        );

        $resultAssoc = $stmt->fetchAllAssociative();
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

    public function getLabelsByLocallangFile($locallangFile): ?array
    {
        /** @var Connection $connection */
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_translatr_domain_model_label');
        $query = <<<SQL
/* select labels from default language */
SELECT
  label.text,
  label.ukey AS ukey,
  label.extension AS extension,
  label.language AS isocode
FROM tx_translatr_domain_model_label AS label
WHERE label.ll_file = ?
;
SQL;
        $stmt = $connection->executeQuery(
            $query,
            [
                $locallangFile
            ],
            [
                ParameterType::STRING,
                ParameterType::STRING
            ]
        );
        return $stmt->fetchAllAssociative();
    }

    public function getLocallangFiles(): ?array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_translatr_domain_model_label');
        return $queryBuilder
            ->select('label.ll_file', 'label.language')
            ->from('tx_translatr_domain_model_label', 'label')->groupBy('label.ll_file',
                'label.language')->executeQuery()->fetchAllAssociative();
    }

    protected function wrapArrayByQuote(array $arr): array
    {
        return array_map(function ($k) {
            return '\'' . $k . '\'';
        }, $arr);
    }
}
