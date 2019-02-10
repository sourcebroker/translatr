<?php

namespace SourceBroker\Translatr\Utility;

/**
 * Class ArrayUtility
 *
 * @package SourceBroker\Translatr\Utility
 */
class ArrayUtility
{

    /**
     * @param $array
     * @param $keyField
     *
     * @return array
     */
    public static function combineWithSubarrayFieldAsKey($array, $keyField)
    {
        return array_combine(array_map(function ($result) use ($keyField) {
            return $result[$keyField] ?: null;
        }, $array), $array);
    }
}