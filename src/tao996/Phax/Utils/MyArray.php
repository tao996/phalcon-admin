<?php

namespace Phax\Utils;

class MyArray
{
    /**
     * 查询数组中满足条件的索引
     * @param array $rows
     * @param callable(mixed $value, int|string $key):bool $predicate
     * @param bool $must 如果没有找到，则异常
     * @return int|string|false
     * @throws \Exception
     */
    public static function indexOf(array $rows, callable $predicate, bool $must = false): int|string|false
    {
        foreach ($rows as $key => $value) {
            if (call_user_func($predicate, $value, $key) === true) {
                return $key;
            }
        }
        if ($must) {
            throw new \Exception('could not found index in rows');
        }
        return false;
    }

    /**
     * 交换数组的元素
     * @param array $rows
     * @param int $key1
     * @param int $key2
     * @return array
     * @throws \Exception
     */
    public static function swap(array &$rows, int $key1, int $key2)
    {
        if (!isset($rows[$key1]) || !isset($rows[$key2])) {
            throw new \Exception('key out of range');
        }
        if ($key1 == $key2) {
            return $rows;
        }
        $temp = $rows[$key1];
        $rows[$key1] = $rows[$key2];
        $rows[$key2] = $temp;
        return $rows;
    }
}