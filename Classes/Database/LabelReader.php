<?php

declare(strict_types=1);

namespace SourceBroker\Translatr\Database;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use SourceBroker\Translatr\Domain\Model\Dto\BeLabelDemand;
use SourceBroker\Translatr\Domain\Repository\LabelRepository;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

final readonly class LabelReader
{
    public function __construct(private ConnectionPool $connectionPool) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findDemandedForBe(BeLabelDemand $demand, ?string $locallangFile = null): array
    {
        if (!$demand->isValid()) {
            return [];
        }
        $parameters = [
            'extension' => $demand->getExtension(),
            'languages' => array_values($demand->getLanguages() ?: ['default']),
        ];
        $types = [
            'extension' => ParameterType::STRING,
            'languages' => ArrayParameterType::STRING,
        ];
        $keyWhere = '';
        if ($locallangFile !== null) {
            $keyWhere = ' AND label.ll_file = :file ';
            $parameters['file'] = $locallangFile;
            $types['file'] = ParameterType::STRING;
        }
        if ($demand->getKeys()) {
            $keyWhere .= ' AND label.ukey IN (:keys) ';
            $parameters['keys'] = array_values($demand->getKeys());
            $types['keys'] = ArrayParameterType::STRING;
        }
        $query = <<<SQL
/* select labels from default language */
(
SELECT
  label.uid,
  label.language,
  label.ukey,
  0 AS parent_uid,
  label.text,
  label.description,
  label.ll_file,
  label.ll_file_index,
  label.tags,
  label.extension,
  label.modify
FROM tx_translatr_domain_model_label AS label
WHERE label.language = "default"
  AND label.extension = :extension
  $keyWhere
) UNION (
/* select labels for specified languages */
SELECT
  label.uid,
  label.language,
  label.ukey,
  parent.uid AS parent_uid,
  label.text,
  label.description,
  label.ll_file,
  label.ll_file_index,
  label.tags,
  label.extension,
  label.modify
FROM tx_translatr_domain_model_label AS label
  LEFT JOIN tx_translatr_domain_model_label AS parent
    ON (parent.language = "default" AND parent.ukey = label.ukey AND parent.ll_file = label.ll_file)
WHERE label.language IN (:languages)
  AND parent.extension = :extension
  $keyWhere
);
SQL;
        /** @var Connection $connection */
        $connection = $this->connectionPool->getConnectionForTable(LabelRepository::TABLE);
        $stmt = $connection->executeQuery($query, $parameters, $types);

        $results = $stmt->fetchAllAssociative();
        $processedResults = [];

        foreach ($results as $result) {
            $uid = (int)$result['uid'];
            if ($result['language'] === 'default') {
                // record in default language are treated as parents
                $processedResults[$uid] = $result;
                $processedResults[$uid]['language_childs'] = [];
            }
        }

        foreach ($results as $result) {
            $parentUid = (int)$result['parent_uid'];
            $language = (string)$result['language'];
            if ($language !== 'default' && isset($processedResults[$parentUid])) {
                // add as a child to parent record
                $processedResults[$parentUid]['language_childs'][$language] = $result;
            }
        }

        return $processedResults;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getLabelsByLocallangFile(string $locallangFile): array
    {
        /** @var Connection $connection */
        $connection = $this->connectionPool->getConnectionForTable('tx_translatr_domain_model_label');
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
                $locallangFile,
            ],
            [
                ParameterType::STRING,
            ]
        );
        return $stmt->fetchAllAssociative();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getLocallangFiles(): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_translatr_domain_model_label');
        return $queryBuilder
            ->select('label.ll_file', 'label.language')
            ->from('tx_translatr_domain_model_label', 'label')->groupBy(
                'label.ll_file',
                'label.language'
            )
            ->orderBy('label.ll_file')
            ->addOrderBy('label.language')
            ->executeQuery()->fetchAllAssociative();
    }
}
