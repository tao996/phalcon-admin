<?php

namespace App\Modules\tao\Helper;

use Phax\Support\Exception\BlankException;

class RedirectHelper
{
    public static string $keyRedirect = '_redirect';

    public function __construct(public MyMvcHelper $mvc)
    {
    }

    public function save(string $redirect, array $drivers = ['session']): bool
    {
        if (!empty($redirect)) {
            if (in_array('cookie', $drivers)) {
                $this->mvc->cookies()->set(self::$keyRedirect, $redirect);
//                cookies()->send(); 你需要自己调用
                return true;
            } elseif (in_array('session', $drivers)) {
                $this->mvc->session()->set(self::$keyRedirect, $redirect);
                return true;
            }
        }
        return false;
    }

    public function query(string $defaultValue = ''): string
    {
        return $this->mvc->request()->getQuery(self::$keyRedirect, null, $defaultValue);
    }

    /**
     * 回调地址
     * @param bool $response 是否直接跳转
     * @return string
     */
    public function read(bool $response = true, array $drivers = ['session']): string
    {
        $redirect = $this->mvc->request()->getQuery(self::$keyRedirect);

        if (empty($redirect) && in_array('cookie', $drivers)) {
            if ($this->mvc->cookies()->has(self::$keyRedirect)) {
                $redirect = $this->mvc->cookies()->get(self::$keyRedirect)->getValue();
                $this->mvc->cookies()->delete(self::$keyRedirect);
            }
        }
        if (empty($redirect) && in_array('session', $drivers)) {
            if ($this->mvc->session()->has(self::$keyRedirect)) {
                $redirect = $this->mvc->session()->get(self::$keyRedirect, '', true);
            }
        }

        $href = $redirect ? urldecode($redirect) : $this->mvc->urlWith('/m/tao/index/index');
        if ($response) {
            $this->mvc->responseHelper()->redirect($href);
            throw new BlankException();
        }
        return $href;
    }
}