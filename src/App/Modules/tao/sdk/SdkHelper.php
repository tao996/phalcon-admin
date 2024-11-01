<?php

namespace App\Modules\tao\sdk;


class SdkHelper
{
    private static array $histories = [];

    public static function include($name): void
    {
        if (!isset(self::$histories[$name])) {
            self::$histories[$name] = true;
            require_once __DIR__ . '/' . $name;
        }
    }

    /**
     * @link https://easywechat.com/6.x/
     * @return void
     */
    public static function easyWechat(): void
    {
        // 6.15.2
        self::include('easywechat.phar');
    }

    public static function hybridauth(): void
    {
        self::include('hybridauth.phar');
    }

    public static function qrcode(): void
    {
        self::include('qrcode.phar');
    }

    public static function aliyunCore(): void
    {
        self::include('aliyun//aliyun-php-phar-core.phar');
    }

}