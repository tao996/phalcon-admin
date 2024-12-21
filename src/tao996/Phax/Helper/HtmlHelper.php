<?php
/*
* Copyright (c) 2024-present
* Author: tao996<lvshutao@outlook.com>
* 
* For the full copyright and license information, please view the LICENSE.txt
* file that was distributed with this source code.
*/

namespace Phax\Helper;

use Phalcon\Mvc\View;
use Phax\Support\Logger;

class HtmlHelper
{
    // controller.action 返回的数据在视图中的前缀
    public static string $prefix = 'api';

    /**
     * 视图服务
     * @var View|mixed
     */
    protected View $view;

    public array $viewData = [];

    public function __construct(public MyMvc $mvc)
    {
        if ($this->mvc->di->has('route') && !$this->mvc->route()->isApiRequest()) {
            $this->view = $this->mvc->view();
            $this->view->setVar('vv', $this->mvc);
        }
    }


    /**
     * 获取 view 上所绑定的数据
     * @param string $path
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $path, mixed $default = null)
    {
        return \Phax\Utils\MyData::findWithPath($this->viewData, $path, $default);
    }


    /**
     * 获取控制器 Action 所返回的值
     * @param string $path
     * @param mixed $default
     * @return mixed
     */
    public function pick(string $path, mixed $default = ''): mixed
    {
        return $this->get(self::$prefix . '.' . $path, $default);
    }


    /**
     * 与模板数据比较，如果相等，则输出 $text
     * @param string $path 路径 或者 值
     * @param mixed $text 输出的内容，如果提供，则会直接使用 echo
     * @param mixed $cmpValue 待比较的值，默认为 1
     * @return mixed
     */
    public function pickCompare(string $path, mixed $text = "", mixed $cmpValue = 1): mixed
    {
        $defValue = is_int($cmpValue) ? 0 : '';
        $eq = $this->pick($path, $defValue) == $cmpValue;
        return $eq ? $text : $defValue;
    }

    /**
     * 页面标题
     * @return string
     */
    public function title(): string
    {
        $title = $this->get('html_title');
        if ($title) {
            return $title . ' - ' . $this->mvc->config()->path('app.title');
        } else {
            return $this->mvc->config()->path('app.title');
        }
    }

    /**
     * 通常用于将 php 变量转为 js 布尔值
     * @param bool $condition
     * @return string
     */
    public function boolText(bool $condition): string
    {
        return $condition ? 'true' : 'false';
    }

    /**
     * print the view data，it should be called in debug mode
     * 注意：在 workerman 中使用此方法，会把 $this->viewData 输出到控制台上
     * @param bool $exit
     * @return void
     */
    public function print(bool $exit = true): void
    {
        pr($this->viewData, $exit);
    }

    public function setVars(array $params): static
    {
        $this->viewData = array_merge($this->viewData, $params);
        return $this;
    }

    public function setVar(string $key, $value): static
    {
        $this->viewData[$key] = $value;
        return $this;
    }

    public function setResponseVar(mixed $data): static
    {
        if (is_scalar($data)) {
            $this->setVar('message', $data);
        } else {
            $this->setVar(self::$prefix, $data);
        }
        return $this;
    }

    /**
     * 设置视图数据，并对视图模板进行检查
     * @return void
     * @throws \Exception
     */
    public function doneViewResponse(): void
    {
        $this->mvc->route()->doneView();
        if ($pickview = $this->mvc->route()->pickView(false)) {
            if (!file_exists($pickview)) {
                if (IS_DEBUG) {
                    throw new \Exception('Pick view not exist:' . $pickview . '.[phtml|volt]');
                } else {
                    Logger::error('Pick view not exist:' . $pickview);
                    throw new \Exception('Pick view not exist');
                }
            }
        }
    }


    private array $hasImports = [];
    /**
     * header 文件列表
     * @var array
     */
    private array $headerFiles = [];
    private array $footerFiles = [];
    private array $headerContents = [];
    private array $footerContents = [];

    /**
     * 添加文件到头部
     * @param string $file 本地文件路径，或者 http(s) 文件地址
     * @param int $weight 权重
     * @param string $type 类型，css|js
     * @return self
     */
    public function addHeaderFile(string $file, int $weight = 0, string $type = ''): static
    {
        if (!in_array($file, $this->headerFiles)) {
            $this->headerFiles[] = [$file, $weight, $type];
        }
        return $this;
    }

    /**
     * 追加内容到 header
     * @param string $content
     * @param string $type 内容类型，css 或者 js
     * @return $this
     */
    public function addHeaderContent(string $content, string $type = 'css'): static
    {
        $this->headerContents[] = [$type, $content];
        return $this;
    }

    /**
     * 添加文件到底部
     * @param string $file 本地文件路径，或者 http(s) 文件地址
     * @param int $weight 权重
     * @param string $type css|js
     * @return self
     */
    public function addFooterFile(string $file, int $weight = 0, string $type = ''): static
    {
        if (!in_array($file, $this->footerFiles)) {
            $this->footerFiles[] = [$file, $weight, $type];
        }
        return $this;
    }

    /**
     * 追加内容到底部
     * @param string $content
     * @param string $type 内容类型，css 或者 js
     * @return $this
     */
    public function addFooterContent(string $content, string $type = 'js'): static
    {
        $this->footerContents[] = [$type, $content];
        return $this;
    }

    /**
     * 排序
     * @param array $data
     */
    private function sortByWeight(array &$data): void
    {
        usort($data, function ($v1, $v2) {
            return $v1[1] - $v2[1];
        });
    }

    /**
     * 输出头部脚本样式
     * @return void
     */
    public function outputHeaders(): void
    {
        $this->sortByWeight($this->headerFiles);
        foreach ($this->headerFiles as $file) {
            $this->includeAssetsFile($file[0], $file[2]);
        }
        foreach ($this->headerContents as $content) {
            if ($content[0] === 'css') {
                echo '<style type="text/css">', $content[1], '</style>';
            } elseif ('js' === $content[0]) {
                echo '<script type="text/javascript">', $content[1], '</script>';
            }
        }
    }

    /**
     * 输入底部脚本样式
     * @return void
     */
    public function outputFooters(): void
    {
        $this->sortByWeight($this->footerFiles);
        foreach ($this->footerFiles as $file) {
            $this->includeAssetsFile($file[0], $file[2]);
        }
        foreach ($this->footerContents as $content) {
            if ($content[0] === 'css') {
                echo '<style type="text/css">', $content[1], '</style>';
            } elseif ('js' === $content[0]) {
                echo '<script type="text/javascript">', $content[1], '</script>';
            }
        }
    }


    public function includeAssetsFile(string $file, string $type = ''): bool
    {
        if (in_array($file, $this->hasImports)) {
            return false;
        }
        // 判断类型
        if ($type == '') {
            if (str_ends_with($file, '.css')) {
                $type = 'css';
            } elseif (str_ends_with($file, '.js')) {
                $type = 'js';
            } else {
                Logger::warning('unknown assets file type for:' . $file);
                return false;
            }
        }
        // 是否是 http 地址
        $http = str_starts_with($file, 'https://') || str_starts_with($file, 'http://');
        // 如果是本地文件，则优先检查是否有 min 文件
        if (!$http) {
            if (str_starts_with($file, PATH_ROOT)) {
                $file = $this->getLocalFilePath($file); // 检查是否存在压缩文件
                if (!file_exists($file)) {
                    return false;
                }
            } else {
                // 映射到 module/project 的脚本文件
                $file = \Phax\Support\Config::$local_assets_origin . $file;
                $http = true;
            }
        }
        if ('css' == $type) {
            if ($http) {
                echo '<link rel="stylesheet" type="text/css" href="', $file, '">';
            } else {
                echo '<style type="text/css">';
                include $file;
                echo '</style>';
            }
        } elseif ('js' == $type) {
            if ($http) {
                echo '<script src="', $file, '"></script>';
            } else {
                echo '<script>';
                include $file;
                echo '</script>';
            }
        } else {
            Logger::warning('include invalid assets type for:' . $file);
            return false;
        }
        $this->hasImports[] = $file;
        return true;
    }

    /**
     * 如果是本地文件，尝试获取 min 压缩文件路径
     * @param string $file
     * @return string
     */
    public function getLocalFilePath(string $file): string
    {
        if (str_ends_with($file, '.min.js') || str_ends_with($file, '.min.css')) {
            return $file;
        }
        // 检查文件是否存在
        if ($this->checkMinFile($file)) {
            return $file;
        }
        $minFile = str_replace(['.css', '.js'], ['.min.css', '.min.js'], $file);
        return file_exists($minFile) ? $minFile : $file;
    }

    protected function checkMinFile(string $file): string
    {
        return file_exists($file);
    }
}