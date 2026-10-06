<?php

declare(strict_types=1);

namespace SourceBroker\Translatr\Database;

use SourceBroker\Translatr\Domain\Repository\LabelRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;

final readonly class LabelWriter
{
    public function __construct(private ConnectionPool $connectionPool) {}

    public function delete(int $uid): void
    {
        $this->connectionPool->getConnectionForTable(LabelRepository::TABLE)
            ->delete(LabelRepository::TABLE, ['uid' => $uid]);
    }

    public function markModified(int $uid): void
    {
        $this->connectionPool->getConnectionForTable(LabelRepository::TABLE)
            ->update(LabelRepository::TABLE, ['modify' => 1], ['uid' => $uid]);
    }

    public function updateTranslation(int $uid, string $text): void
    {
        $this->connectionPool->getConnectionForTable(LabelRepository::TABLE)
            ->update(LabelRepository::TABLE, ['text' => $text], ['uid' => $uid, 'modify' => 0]);
    }

    public function updateTags(string $key, string $extension, string $path, string $tags): void
    {
        $this->connectionPool->getConnectionForTable(LabelRepository::TABLE)
            ->update(LabelRepository::TABLE, ['tags' => $tags], [
                'extension' => $extension,
                'ukey' => $key,
                'll_file' => $path,
            ]);
    }
}
