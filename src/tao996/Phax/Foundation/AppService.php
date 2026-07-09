<?php

namespace Phax\Foundation;

use Phalcon\Encryption\Security;
use Phalcon\Http\Request;
use Phax\Support\Config;

class AppService
{
    /**
     * @return Config
     */
    public static function config(): Config
    {
        return Application::di()->get('config');
    }

    public static function request(): Request
    {
        return Application::di()->get('request');
    }

    public static function cache(): \Phalcon\Cache\Cache
    {
        return Application::di()->getShared('cache');
    }

    /**
     * 通常生成生成/校验表单 token；对密码进行加密处理
     * Random 生成随机数据；Hash 数据加密
     * Token 用于防止 CSRF 攻击；
     * https://docs.phalcon.io/5.0/en/encryption-security#random
     * @return \Phalcon\Encryption\Security
     */
    public static function security(): Security
    {
        return Application::di()->getShared('security');
    }

    /**
     * 超级管理员 ID
     * @return array
     */
    public static function superAdminIds(): array
    {
        return self::config()->getSuperAdminIds();
    }
}