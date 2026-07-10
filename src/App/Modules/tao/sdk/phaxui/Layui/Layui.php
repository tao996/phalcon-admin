<?php

namespace App\Modules\tao\sdk\phaxui\Layui;

use App\Modules\tao\sdk\phaxui\HtmlAssets;
use Phax\Foundation\AppService;

/**
 * @link https://layui.dev/docs/2/
 */
class Layui
{
    public string $version = '';

    private array $_config = [
        'debug' => IS_DEBUG
    ];
    private bool $hasImportFooter = false;
    private bool $hasImportHeader = false;


    private array $footerJs = [];
    private array $footerCss = [];

    public function __construct(string $version = '2.13.6')
    {
        $this->version = $version;
        $this->header();
    }

    private function header(): void
    {
        if ($this->hasImportHeader) {
            return;
        }
        $this->hasImportHeader = true;
        $html = AppService::html();

        if (HtmlAssets::isLocal()) {
            $html->addHeaderFile(
                '/mstatic/tao/assets/layui/' . $this->version . '/css/layui.css'
            );
            $html->addHeaderFile('/mstatic/tao/assets/font-awesome/4.7.0/css/font-awesome.min.css'
            );
        } else {
            $html->addHeaderFile(HtmlAssets::$cdn . 'layui/' . $this->version . '/css/layui.min.css');
            $html->addHeaderFile(HtmlAssets::$cdn . 'font-awesome/4.7.0/css/font-awesome.min.css');
        }
        $html->addHeaderFile(__DIR__ . '/index.css', 0, 'css');
        $html->addHeaderFile(__DIR__ . '/upload.css', 0, 'css');
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

    /**
     * 需要单独输出脚本（还没有找到完美的解决方案）
     * @return $this
     */
    public function footer(): static
    {
        if ($this->hasImportFooter) {
            return $this;
        }
        $this->hasImportFooter = true;

        if (HtmlAssets::isLocal()) {
            echo '<script src="/mstatic/tao/assets/layui/' . $this->version . '/layui.js"></script>';
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

        include HtmlAssets::tryMinFile(__DIR__ . '/index.js');
        echo '</script>';
        echo '<script>' . join('', $this->footerJs) . '</script>' . '<style>' . join('', $this->footerCss) . '</style>';
        return $this;
    }

    public function appendFooterJs(string $content): void
    {
        $this->footerJs[] = $content;
    }

    public function appendFooterCss(string $content): void
    {
        $this->footerCss[] = $content;
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
}