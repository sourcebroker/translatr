<?php

declare(strict_types=1);

namespace SourceBroker\Translatr\Database;

use TYPO3\CMS\Core\Database\ConnectionPool;

final readonly class RootPageProvider
{
    public function __construct(private ConnectionPool $connectionPool) {}

    public function getRootPage(): int
    {
        $query = $this->connectionPool->getQueryBuilderForTable('pages');
        return (int)$query->select('uid')->from('pages')
            ->where($query->expr()->eq('pid', 0), $query->expr()->eq('deleted', 0))
            ->orderBy('uid')->setMaxResults(1)->executeQuery()->fetchOne();
    }
}
