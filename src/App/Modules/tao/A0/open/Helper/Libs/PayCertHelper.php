<?php

namespace App\Modules\tao\A0\open\Helper\Libs;

/**
 * 商户证书服务
 */
class PayCertHelper
{
    /**
     * 保存证书的路径，以 '/' 结尾
     * @return string
     * @throws \Exception
     */
    public static function dir(): string
    {
        $dir = PATH_STORAGE_DATA . 'pay/';
        if (!file_exists($dir)) {
            if (mkdir($dir)) {
                return $dir;
            } else {
                throw new \Exception('could not mkdir the pay cert direction');
            }
        }
        return $dir;
    }
}