<?php

namespace App\Modules\tao\A0\open\sdk;

class SdkHelper
{
    public static function autoload()
    {
        static $hasLoad = false;
        if (!$hasLoad) {
            $hasLoad = true;
            \App\Modules\tao\sdk\SdkHelper::easyWechat();

            loader()
                ->addNamespace('EasyTiktok', __DIR__ . '/easytiktok/src')
                ->register();
        }
    }
}