<?php

namespace App\Modules\tao\views;

use Phax\Foundation\AppService;

class HtmlAssets
{

    /**
     * 注意：这里的 cdn 只针对 layui, tinymce, awesome 等公共资源生效
     * @var string
     */
    public static string $cdn = '/assets/';
    /**
     * cn|ncn|self 自定义
     * @var string
     */
    public static string $cdnLocate = 'self';

    /**
     * 设置 CDN 地址
     * @param string $cdnLocate 默认从 app.cdn 读取，可选 cn|ncn
     * @return void
     */
    public static function initWithCdn(string $cdnLocate = ''): void
    {
        if (empty($cdnLocate)) {
            $cdnLocate = AppService::config()->getString('app.assets.cdn', 'self');
        }

        switch ($cdnLocate) {
            case 'cn':
                self::$cdnLocate = 'cn';
                self::$cdn = 'https://cdn.staticfile.org/';
                break;
            case 'ncn':
                self::$cdnLocate = 'ncn';
                // https://cdnjs.com/
                self::$cdn = 'https://cdnjs.cloudflare.com/ajax/libs/';
                break;
            default:
                if ($cdnLocate == 'self') {
                    self::$cdnLocate = 'self';
                } else {
                    self::$cdnLocate = 'other';
                    self::$cdn = $cdnLocate;
                }
                break;
        }
    }

    /**
     * 是否本地部署
     * @return bool
     */
    public static function isLocal(): bool
    {
        return self::$cdnLocate == 'self';
    }

}