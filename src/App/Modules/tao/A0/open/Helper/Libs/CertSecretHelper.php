<?php

namespace App\Modules\tao\A0\open\Helper\Libs;

class CertSecretHelper
{
    /**
     * @param string $data 文件内容
     * @param int $index 替换的位置
     * @param int $len 替换的长度
     * @param int $ord 递增数
     * @return string 替换后的内容
     */
    public static function encryptData(string $data, int $index, int $len = 1, int $ord = 1): string
    {
        $substr = substr($data, $index, $len);
        for ($i = 0; $i < $len; $i++) {
            $substr[$i] = chr(ord($substr[$i]) + $ord);
        }
        return substr($data, 0, $index) . $substr . substr($data, $index + $len);
    }

    public static function decryptData(string $data, int $index, int $len = 1, int $ord = 1): string
    {
        $substr = substr($data, $index, $len);
        for ($i = 0; $i < $len; $i++) {
            $substr[$i] = chr(ord($substr[$i]) - $ord);
        }
        return substr($data, 0, $index) . $substr . substr($data, $index + $len);
    }

}