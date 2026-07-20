<?php

namespace App\Modules\tao\views\assets\vue;

use App\Modules\tao\views\HtmlAssets;

class AssetsVue
{
    public static string $vue_version = '3.3.9';

    /**
     * 在页面顶部引入 vue 脚本
     * @return void
     */
    public static function header(): void
    {
        if (HtmlAssets::isLocal()) {
            echo '<script src="/mstatic/tao/assets/vue/' . self::$vue_version . '/vue.global.prod.min.js"></script>';
        } else {
            echo '<script src="' . HtmlAssets::$cdn . 'vue/' . self::$vue_version . '/vue.global.prod.min.js"></script>';
        }
    }
}