<?php

namespace SourceBroker\Translatr\Database;

use SourceBroker\Translatr\Domain\Model\Dto\BeLabelDemand;

interface DatabaseInterface
{
    public function update($table, array $set, array $condition);

    public function getRootPage(): int;

    public function findDemandedForBe(BeLabelDemand $demand);

    public function getLabelsByLocallangFile($locallangFile): ?array;

    public function getLocallangFiles(): ?array;
}
