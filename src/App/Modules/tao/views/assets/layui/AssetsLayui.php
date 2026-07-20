<?php

namespace App\Modules\tao\views\assets\layui;

use App\Modules\tao\views\HtmlAssets;
use Phax\Foundation\AppService;
use Phax\Helper\HtmlHelper;
use const App\Modules\tao\Helper\PATH_MODULE_TAO_ASSETS;

/**
 * @link https://layui.dev/docs/2/
 */
class AssetsLayui
{
    public string $version = '';

    private array $_config = [
        'debug' => IS_DEBUG
    ];
    private bool $hasImportFooter = false;
    private bool $hasImportHeader = false;


    private array $footerJs = [];
    private array $footerCss = [];
    private bool $min;

    public function __construct(string $version = '2.13.6')
    {
        $this->version = $version;
        $this->min = AppService::config()->getBoolean('app.assets.min');

        $obj = $this;
        $html = AppService::html();
        $html->addHeaderFile(PATH_APP_MODULES . 'tao/views/layui/tao.css', local: true);
        $html->beforeOutputHeaders[] =
            function () use ($obj) {
                $obj->header();
            };
        $html->afterOutputFooters[] = function () use ($obj, $html) {
            $obj->footer();
            $html->includeAssetsFile(PATH_APP_MODULES . 'tao/views/layui/tao.js', type: 'js', local: true);
        };
    }

    /**
     * 初始化，将 layui 相关的文件添加 headerFile 中，等待 HtmlHelper 输出到页面中
     * @return void
     */
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
        /// 添加自定义的 css/js
//        ddd($this->min, HtmlHelper::getAssetPath(__DIR__ . '/index.css', $this->min));
        $html->addHeaderFile(__DIR__ . '/index.css', local: true);
        $html->addHeaderFile(__DIR__ . '/upload.css', local: true);
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
     * 需要单独输出 layui 脚本（还没有找到完美的解决方案）
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
/// 将配置信息输出为 js 对象
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
        if ($filepath = HtmlHelper::getAssetPath(__DIR__ . '/index.js', $this->min)) {
            require_once $filepath;
        }

        echo '</script>';
        echo '<script>' . join('', $this->footerJs) . '</script>' . '<style>' . join('', $this->footerCss) . '</style>';
        return $this;
    }

    /**
     * 添加 layui js 脚本内容
     * @param string $content
     * @return void
     */
    public function appendFooterJs(string $content): void
    {
        $this->footerJs[] = $content;
    }

    /**
     * 添加 layui css 样式内容
     */
    public function appendFooterCss(string $content): void
    {
        $this->footerCss[] = $content;
    }
}