<?php

declare(strict_types=1);

namespace SourceBroker\Translatr\Database;

use SourceBroker\Translatr\Domain\Model\Dto\BeLabelDemand;

interface DatabaseInterface
{
    /**
     * @param array<string, mixed> $condition
     */
    public function delete(string $table, array $condition): void;

    /**
     * @param array<string, mixed> $set
     * @param array<string, mixed> $condition
     */
    public function update(string $table, array $set, array $condition): void;

    public function getRootPage(): int;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findDemandedForBe(BeLabelDemand $demand): array;

    /**
     * @return list<array<string, mixed>>|null
     */
    public function getLabelsByLocallangFile(string $locallangFile): ?array;

    /**
     * @return list<array<string, mixed>>|null
     */
    public function getLocallangFiles(): ?array;
}
