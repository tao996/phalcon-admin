<?php

namespace App\Modules\tao\sdk\phaxui;

use Phax\Foundation\Application;
use Phax\Support\Config;

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
            /**
             * @var Config $config
             */
            $config = Application::di()->get('config');
            $cdnLocate = $config->path('app.cdn_locate', 'self');
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


    /**
     * 尝试引用 min 压缩文件
     * @param string $file
     * @return string
     */
    public static function tryMinFile(string $file): string
    {
        if (str_ends_with($file, '.min.js') || str_ends_with($file, '.min.css')) {
            return $file;
        }
        if (HtmlAssets::isLocal() && file_exists($file)) {
            return $file;
        }
        $minFile = str_replace(['.css', '.js'], ['.min.css', '.min.js'], $file);
        return file_exists($minFile) ? $minFile : $file;
    }

}