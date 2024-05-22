<?php

namespace SourceBroker\Translatr\Utility;

class ArrayUtility
{

    public static function combineWithSubarrayFieldAsKey(array $array, string $keyField)
    {
        return array_combine(array_map(function ($result) use ($keyField) {
            return $result[$keyField] ?: null;
        }, $array), $array);
    }
}
