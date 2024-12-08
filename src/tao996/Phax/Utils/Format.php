<?php

namespace Phax\Utils;

class Format
{
    /**
     * 将时间戳进行格式化
     * @param array $row
     * @param string $name
     * @param string $format
     * @return string
     */
    public static function dateTimeText(array $row, string $name = 'created_at', string $format = 'Y-m-d H:i'): string
    {
        if (!empty($row[$name])) {
            return date($format, $row[$name]);
        }
        return '';
    }

    static array $size = array('B', 'K', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y');

    /**
     * 将表示文件大小的字节数转成友好字符串
     * @param float|numeric $bytes 字节数，如 1024
     * @param int $decimals 保留的小数点位，如 2
     * @return string 1.00K 或者 1K
     */
    public static function humanFileSize(float|int|string $bytes, int $decimals = 0): string
    {
        // https://www.php.net/manual/zh/function.filesize.php
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @self::$size[$factor];
    }

    /**
     * 获取文件字节
     * @param string $humanSize 表示文件大小的友好字符串，如 1K
     * @return int 字符数 1024
     * @throws \Exception
     */
    public static function getBytesFromSize(string $humanSize): int
    {
        $data = self::splitFileSize($humanSize);
        // https://stackoverflow.com/questions/11807115/php-convert-kb-mb-gb-tb-etc-to-bytes
        $exponent = array_flip(self::$size)[$data[1]] ?? null;
        if ($exponent === null) {
            return 0;
        }
        return $data[0] * (1024 ** $exponent);
    }

    /**
     * 对表示文件尺寸的数字进行切割
     * @param float|numeric|string $size 文件尺寸 如 5KB,5k
     * @return array [数字,小写单位] 如 [5, 'k']
     * @throws \Exception
     */
    public static function splitFileSize(float|int|string $size): array
    {
        if (is_numeric($size) || is_float($size)) {
            return [$size, 'B'];
        } else {
            preg_match('|^([\d\.]+)([a-zA-Z]{1,2})$|', $size, $matches);
            if (!isset($matches[2])) {
                throw new \Exception('请检查待转换的文件大小格式:' . $size);
            }
            $unit = strlen($matches[2]) == 2 ? substr($matches[2], 0, 1) : $matches[2];
            return [$matches[1], strtoupper($unit)];
        }
    }
}