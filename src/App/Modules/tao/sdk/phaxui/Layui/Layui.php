<?php

namespace App\Modules\tao\sdk\phaxui\Layui;

use Phax\Traits\Singleton;
use App\Modules\tao\sdk\phaxui\HtmlAssets;

/**
 * @link https://layui.dev/docs/2/
 */
class Layui
{
    use  Singleton;

    public string $version = '2.9.18';

    /**
     * @var array js 配置信息
     */
    public static array $globalJsWindowConfig = [];
    private array $_config = [
        'debug' => IS_DEBUG
    ];

    /**
     * @var bool 是否使用 include 的方式包含 Layui 目录下的 index.css, index.js, upload.css 文件
     */
    static bool $includeLocal = true;

    protected function __construct()
    {
    }

    /**
     * 添加配置信息
     * @param array $config
     * @return self
     */
    public function addWindowConfig(array $config = []): static
    {
        $this->_config = array_merge($this->_config, $config);
        return $this;
    }

    public function header(): static
    {
        if (HtmlAssets::isLocal()) {
            echo '<link rel="stylesheet" href="/massets/tao/layui/' . $this->version . '/css/layui.css" />';
            echo '<link rel="stylesheet" href="/massets/tao/font-awesome/4.7.0/css/font-awesome.min.css" />';
        } else {
            // https://cdn.staticfile.org/layui/2.9.11/css/layui.min.css
            // https://cdnjs.cloudflare.com/ajax/libs/layui/2.9.11/css/layui.min.css
            // https://cdn.staticfile.org/layui/2.9.11/layui.min.js
            // https://cdnjs.cloudflare.com/ajax/libs/layui/2.9.11/layui.min.js
            echo '<link rel="stylesheet" href="' . HtmlAssets::$cdn . 'layui/' . $this->version . '/css/layui.min.css" />';
            echo '<link rel="stylesheet" href="' . HtmlAssets::$cdn . 'font-awesome/4.7.0/css/font-awesome.min.css" />';
        }

        if (self::$includeLocal) {
            echo '<style type="text/css">';
            include_once HtmlAssets::tryMinFile(__DIR__ . '/index.css');
            include_once HtmlAssets::tryMinFile(__DIR__ . '/upload.css');
            echo '</style>';
        }
        return $this;
    }

    public function selectHeader(): void
    {
        echo '<style>';
        echo <<<CSS
html, body {
    margin: 0;
    padding: 0;
}

.layui-table-tool-temp {
    padding-right: 0;
}

.input-keyword {
    display: inline-block;
    width: 190px;
    line-height: 38px;
    height: 38px;
    border: 1px solid #C9C9C9;
}
CSS;
        echo '</style>';
    }

    private bool $hasImport = false;

    public function footer(): static
    {
        if ($this->hasImport) {
            return $this;
        }
        if (HtmlAssets::$cdnLocate == 'self') {
            echo '<script src="/massets/tao/layui/' . $this->version . '/layui.js"></script>';
        } else {
            echo '<script src="' . HtmlAssets::$cdn . 'layui/' . $this->version . '/layui.min.js"></script>';
        }
        echo '<script type="text/javascript">const $ = layui.jquery,layer = layui.layer, form = layui.form, laydate= layui.laydate,util=layui.util,table=layui.table;';

        echo 'window.CONFIG = {';
        foreach ($this->_config as $key => $v) {
            if (is_string($v)) {
                if (str_contains($v, '\'')) {
                    echo '"' . $key . '"', ':"', $v, '",';
                } else {
                    echo '"' . $key . '"', ":'", $v, "',";
                }
            } elseif (is_bool($v)) {
                echo '"' . $key . '"', ':', $v ? 'true' : 'false', ',';
            } else {
                echo '"' . $key . '"', ':', $v, ',';
            }
        }
        echo '};';

        if (self::$includeLocal) {
            include_once HtmlAssets::tryMinFile(__DIR__ . '/index.js');
        }
        echo '</script>';
        $this->hasImport = true;

        return $this;
    }

}