<?php

namespace Phax\Utils;

class MyBc
{
    /**
     * 数组中某个字段相加
     */
    public static function addWith(array &$data, $key, $value, int $scale = 2): void
    {
        $v = $data[$key] ?? 0;
        $data[$key] = bcadd('' . $v, '' . $value, $scale);
    }

    /**
     * 数组中某个字段相减
     */
    public static function subWith(array &$data, $key, $value, int $scale = 2): void
    {
        $v = $data[$key] ?? 0;
        $data[$key] = bcsub('' . $v, '' . $value, $scale);
    }

    /**
     * 数组中某个字段相乘
     */
    public static function mulWith(array &$data, $key, $value, int $scale = 2): void
    {
        $v = $data[$key] ?? 0;
        $data[$key] = bcmul('' . $v, '' . $value, $scale);
    }

    /**
     * 数组中某个字段相除
     */
    public static function divWith(array &$data, $key, $value, int $scale = 2): void
    {
        if ($value == 0) {
            throw new \Exception('除数不能为0');
        }
        $v = $data[$key] ?? 0;
        $data[$key] = bcdiv('' . $v, '' . $value, $scale);
    }

    /**
     * 相加
     */
    public static function add($d1, $d2, int $scale = 2): string
    {
        return bcadd('' . $d1, '' . $d2, $scale);
    }

    /**
     * 相减
     */
    public static function sub($d1, $d2, int $scale = 2): string
    {
        return bcsub('' . $d1, '' . $d2, $scale);
    }

    public static function mul($d1, $d2, int $scale = 2): string
    {
        return bcmul('' . $d1, '' . $d2, $scale);
    }

    public static function div($d1, $d2, int $scale = 2): string
    {
        if ($d2 == 0) {
            throw new \Exception('除数不能为0');
        }
        return bcdiv('' . $d1, '' . $d2, $scale);
    }

    /**
     * @param $d1
     * @param $d2
     * @return int 0: 相等， 1: d1>d2； -1: d1<d2
     */
    public static function cmp($d1, $d2): int
    {
        return bccomp('' . $d1, '' . $d2);
    }

    /**
     * 是否相等
     * @param $d1
     * @param $d2
     * @return bool
     */
    public static function equal($d1, $d2): bool
    {
        return bccomp('' . $d1, '' . $d2) == 0;
    }

    /**
     * @param $d1
     * @param $d2
     * @param float $diffValue 差值，在差值内，可视为相等
     * @return bool
     */
    public static function notEqual($d1, $d2, float $diffValue = 0): bool
    {
        if ($diffValue != 0) {
            return abs(bcsub('' . $d1, '' . $d2)) > $diffValue;
        }
        return bccomp('' . $d1, '' . $d2) != 0;
    }
}