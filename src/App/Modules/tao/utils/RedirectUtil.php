<?php

namespace App\Modules\tao\utils;

use Phax\Foundation\AppService;
use Phax\Support\Exception\BlankException;

class RedirectUtil
{
    public static string $keyRedirect = '_redirect';

    public static function save(string $redirect, array $drivers = ['session']): bool
    {
        if (!empty($redirect)) {
            if (in_array('cookie', $drivers)) {
                AppService::cookies()->set(self::$keyRedirect, $redirect);
//                cookies()->send(); 你需要自己调用
                return true;
            } elseif (in_array('session', $drivers)) {
                AppService::session()->set(self::$keyRedirect, $redirect);
                return true;
            }
        }
        return false;
    }

    public static function query(string $defaultValue = ''): string
    {
        return AppService::request()->getQuery(self::$keyRedirect, null, $defaultValue);
    }

    /**
     * 回调地址
     * @param bool $response 是否直接跳转
     * @return string
     */
    public static function read(bool $response = true, array $drivers = ['session']): string
    {
        $redirect = AppService::request()->getQuery(self::$keyRedirect);

        if (empty($redirect) && in_array('cookie', $drivers)) {
            if (AppService::cookies()->has(self::$keyRedirect)) {
                $redirect = AppService::cookies()->get(self::$keyRedirect)->getValue();
                AppService::cookies()->delete(self::$keyRedirect);
            }
        }
        if (empty($redirect) && in_array('session', $drivers)) {
            if (AppService::session()->has(self::$keyRedirect)) {
                $redirect = AppService::session()->get(self::$keyRedirect, '', true);
            }
        }

        $href = $redirect ? urldecode($redirect) : AppService::urlWith('/m/tao/index/index');
        if ($response) {
            ResponseUtil::redirect($href);
            throw new BlankException();
        }
        return $href;
    }
}