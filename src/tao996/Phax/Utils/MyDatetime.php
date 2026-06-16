<?php

namespace Phax\Utils;

class MyDatetime
{
    /**
     * 万能日期解析
     * 支持：5月22日、2026年5月22日、2026/05/22、5.22、Excel数字(46164),5.10
     * @param mixed $date 输入日期
     * @param string $defaultYear 默认年份（如果不指定则使用当前年份）
     * @return string 标准格式 Y-m-d
     */
    public static function parseDate(mixed $date, string $defaultYear = ''): string
    {
        // 空值直接返回
        if (empty($date) && $date !== 0 && $date !== 0.0) return '';

        // 处理数字类型：Excel 中 "6.10" 存储为 6.1，用 sprintf 恢复精度
        if (is_float($date) || is_int($date)) {
            $str = sprintf("%.2f", $date);
            // 去掉末尾的 .00（纯整数月份如 6 月）
            if (str_ends_with($str, '.00')) {
                $str = substr($str, 0, -3);
            }
        } else {
            $str = trim((string)$date);
        }

        // 1. 处理 Excel 数字日期（如 46164）
        if (ctype_digit($str) && $str > 20000) {
            return date('Y-m-d', ($str - 25569) * 86400);
        }

        // 2. 替换各种中文符号、分隔符为统一格式
        $replace = [
            '年' => '-', '月' => '-', '日' => '',
            '/' => '-', '.' => '-', ' ' => ''
        ];
        $str = strtr($str, $replace);

        // 3. 格式 5-22 → 补年份
        if (preg_match('/^\d{1,2}-\d{1,2}$/', $str)) {
            if (empty($defaultYear)) {
                $defaultYear = date('Y');
            }
            $str = $defaultYear . '-' . $str;
        }

        // 4. 格式 2026-5-22 → 自动补零
        try {
            $dt = new \DateTime($str);
            return $dt->format('Y-m-d');
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * 判断是否为 yyyy-mm-dd 格式
     * @param string $date
     * @return bool
     */
    public static function isDate(string $date): bool
    {
        return preg_match('|\d{4}-\d{1,2}-\d{1,2}|', $date) == 1;
    }

    /**
     * 日期必须是一个标准的 yyyy-mm-dd 格式
     * @param string $date
     * @return void
     * @throws \Exception
     */
    public static function mustDate(string $date): void
    {
        if (!MyDatetime::isDate($date)) {
            throw new \Exception('日期格式错误:' . $date);
        }
    }

    /**
     * 是否为 yyyy-mm 格式
     * @param string $month
     * @return bool
     */
    public static function isMonth(string $month): bool
    {
        return preg_match('|\d{4}-\d{1,2}|', $month) == 1;
    }

    public static function mustMonth(string $month): void
    {
        if (!MyDatetime::isMonth()) {
            throw new \Exception('月份格式错误:' . $month);
        }
    }

    public static function isYear(string $year): bool
    {
        return preg_match('|\d{4}|', $year) == 1;
    }

    public static function mustYear(string $year): void
    {
        if (!MyDatetime::isYear()) {
            throw new \Exception('年份格式错误:' . $year);
        }
    }

    /**
     * 返回第1天和最后一天
     * @param string $month 待查询月份
     * @param bool $withTime 是否需要带上时间 00:00:00, 23:59:59
     * @return array
     */
    static public function monthDateRange(string $month, bool $withTime = false): array
    {
        // 查询月份 yyyy-md 的第1天和最后一天
        $firstDay = $month . '-01';
        $lastDay = date('Y-m-t', strtotime($firstDay));
        return $withTime ? [$firstDay . ' 00:00:00', $lastDay . ' 23:59:59'] : [$firstDay, $lastDay];
    }

    /**
     * 比较 $date1 是否大于（晚于）等于 $date2
     * @param string $date1
     * @param string $date2
     * @return bool
     */
    static public function dateGte(string $date1, string $date2): bool
    {
        return strtotime($date1) >= strtotime($date2);
    }
}