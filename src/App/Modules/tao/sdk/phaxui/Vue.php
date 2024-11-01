<?php

namespace App\Modules\tao\sdk\phaxui;

class Vue
{
    public static string $version = '3.3.9';

    /**
     * 使用注意：如果使用 form，那么 mount('#tao-app') 中的 id 必须在 layui-form 内部
     * @return void
     */
    public static function js()
    {
        if (HtmlAssets::isLocal()) {
            echo '<script src="/massets/tao/vue/' . self::$version . '/vue.global.prod.min.js"></script>';
        } else {
            echo '<script src="' . HtmlAssets::$cdn . 'vue/' . self::$version . '/vue.global.prod.min.js"></script>';
        }
    }
}