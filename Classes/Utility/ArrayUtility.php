<?php

declare(strict_types=1);

namespace SourceBroker\Translatr\Utility;

class ArrayUtility
{
    /**
     * @param array<int|string, array<string, mixed>> $array
     * @return array<int|string, array<string, mixed>>
     */
    public static function combineWithSubarrayFieldAsKey(array $array, string $keyField): array
    {
        return array_combine(array_map(fn($result) => $result[$keyField] ?: null, $array), $array);
    }
}
