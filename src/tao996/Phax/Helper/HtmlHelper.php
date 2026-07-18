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
use Phax\Foundation\AppService;
use Phax\Foundation\Context\RouteMatchContext;
use Phax\Support\Exception\BusinessException;
use Phax\Support\Logger;
use Phax\Utils\MyData;

/**
 * 模板 html 代码辅助
 */
class HtmlHelper
{
    // controller.action 返回的数据在视图中的前缀
    public static string $prefix = 'api';

    /**
     * 视图服务
     * @var View|mixed
     */
    protected View $view;
    /**
     * 禁用主布局 views/index.phtml
     */
    public bool $disabledMainLayout = false;
    /**
     * 手动指定布局文件，否则从模块或项目中获取
     */
    public string $mainLayoutView = '';
    /**
     * @var array 绑定到视图上的数据
     */
    public array $viewData = [];
    /**
     * @var bool 是否启用本地资源压缩
     */
    public bool $min = false;

    public function __construct()
    {
        if (AppService::has('context') && !AppService::context()->isApiRequest()) {
            $this->view = AppService::view();
            $this->view->setVar('vv', $this);
        }
        $this->min = AppService::config()->getBoolean('app.assets.min');
    }

    /**
     * 添加当前视图目录下的资源文件，通常是静态资源文件
     * @param $file string 待添加文件名称，如 tao.css
     * @return void
     */
    public function addViewFile(string $file, string $type = 'css'): void
    {
        $pathFile = AppService::view()->getViewsDir() . $file;
        $this->addHeaderFile($pathFile, type: $type, local: true);
    }

    /**
     * 设置一个原始模板变量
     * @param string $key
     * @param $value
     * @return $this
     */
    protected function setVar(string $key, $value): static
    {
        $this->viewData[$key] = $value;
        return $this;
    }

    /**
     * 获取一个原始模板变量
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getVar(string $key, mixed $default = ''): mixed
    {
        return MyData::get($this->viewData, $key, $default);
    }

    /**
     * 获取 view 上所绑定的原始数据
     * @param string $path 链接路径
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
     * 通常用于使用整数来表示布尔状态的字段，比如状态 status == 1 表示激活
     * @param string $path
     * @param int $active 默认为 1
     * @return bool
     */
    public function pickIntBoolean(string $path, int $active = 1): bool
    {
        return $this->pick($path, 0) == $active;
    }

    /**
     * 设置 html 标题
     * @param string $title
     * @return $this
     */
    public function setHtmlTitle(string $title): static
    {
        $this->setVar('html_title', $title);
        return $this;
    }

    /**
     * 页面标题
     * @return string
     */
    public function getHtmlTitle(): string
    {
        $title = $this->getVar('html_title');
        if ($title) {
            return $title . ' - ' . AppService::config()->getString('app.title');
        } else {
            return AppService::config()->getString('app.title');
        }
    }

    /**
     * 将 action api 数据转为视图数据
     * @param mixed $data
     * @return $this
     */
    public function setApiResponseVar(mixed $data): static
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

        $this->setVar('language', AppService::getLanguage());

        $context = AppService::context();
        $view = AppService::view();
        $viewDir = $context->getViewDIR();
        $view->setViewsDir($viewDir); // 设置视图目录

        if ($this->disabledMainLayout) {
            $this->view->disableLevel(\Phalcon\Mvc\View::LEVEL_MAIN_LAYOUT);
        } else {
            if ($this->mainLayoutView) {
                $context->mainView = $this->mainLayoutView;
            } else {
                // 布局文件
                $layoutViewPath = $viewDir . DIRECTORY_SEPARATOR . 'index';
                if (file_exists($layoutViewPath . RouteMatchContext::TEMPLATE_SUFFIX)) {
                    $context->mainView = $layoutViewPath;
                } elseif (empty($context->mainView)) {
                    // 模块布局文件
                    if (isset($context->isModule)) {
                        $context->mainView = PATH_APP_MODULES . $context->getViewDIRFor(
                                $context->name,
                            ) . 'index';
                        // 项目布局文件
                    } elseif (!empty($context->isProject)) {
                        $context->mainView = PATH_APP_PROJECTS . $context->getViewDIRFor(
                                $context->name
                            ) . 'index';
                    } elseif ($index = strpos($context->viewpath, DIRECTORY_SEPARATOR . 'A0' . DIRECTORY_SEPARATOR)) {
                        $context->mainView = $context->getViewDIRFor(
                                substr($context->viewpath, 0, $index)
                            ) . 'index';
                    }
                }
            }
            // 如果存在布局文件
            if (!empty($context->mainView)) {
                $view->setMainView($context->mainView);
            }
        }
        // 检查渲染文件
        $pickViewPath = $context->getPathOfRenderViewTemplate();
        if (file_exists($pickViewPath . RouteMatchContext::TEMPLATE_SUFFIX)) {
            $view->pick($context->getPickView()); // 你可以在控制器中随机修改
        } else {
            if (IS_DEBUG) {
                ddd('选择器模板不存在',
                    AppService::context()->data(),
                );
            } else {
                throw new BusinessException('待渲染的模板不存在');
            }
        }
    }

    /**
     * 用于标识资源是否已经被引用过
     * @var array
     */
    private array $hasImports = [];
    /**
     * header 头部文件链接列表 [文件链接， 权重， 类型，是否本地]
     * @var array
     */
    private array $headerFiles = [];
    /**
     * @var array 底部文件链接列表 [文件链接， 权重， 类型，是否本地]
     */
    private array $footerFiles = [];
    /**
     * @var array 头部内容
     */
    private array $headerContents = [];
    /**
     * @var array 底部内容
     */
    private array $footerContents = [];

    /**
     * 添加文件到头部
     * @param string $file 本地文件路径，或者 http(s) 文件地址
     * @param int $weight 权重
     * @param string $type 类型，css|js
     * @return self
     */
    public function addHeaderFile(string $file, int $weight = 0, string $type = 'css', bool $local = false): static
    {
        if (!in_array($file, $this->headerFiles)) {
            $this->headerFiles[] = [$file, $weight, $type, $local];
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
    public function addFooterFile(string $file, int $weight = 0, string $type = 'js', bool $local = false): static
    {
        if (!in_array($file, $this->footerFiles)) {
            $this->footerFiles[] = [$file, $weight, $type, $local];
        }
        return $this;
    }

    /**
     * 追加内容到底部，不需要添加标签对
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
     * 在 outputHeaders 之前调用
     * @var array
     */
    public array $beforeOutputHeaders = [];
    /**
     * 在 outputHeaders 之后调用
     * @var array
     */
    public array $afterOutputHeaders = [];
    /**
     * @var array 在 outputFooters 之前调用
     */
    public array $beforeOutputFooters = [];
    /**
     * @var array 在 outputFooters 之后调用
     */
    public array $afterOutputFooters = [];

    /**
     * 输出头部脚本样式
     * @return void
     */
    public function outputHeaders(): void
    {
        foreach ($this->beforeOutputHeaders as $item) {
            if (is_callable($item)) {
                $item();
            }
        }
        $this->sortByWeight($this->headerFiles);
        foreach ($this->headerFiles as $file) {
            $this->includeAssetsFile($file[0], $file[2], $file[3]);
        }
        foreach ($this->headerContents as $content) {
            if ($content[0] === 'css') {
                echo '<style type="text/css">', $content[1], '</style>';
            } elseif ('js' === $content[0]) {
                echo '<script type="text/javascript">', $content[1], '</script>';
            }
        }
        foreach ($this->afterOutputHeaders as $item) {
            if (is_callable($item)) {
                $item();
            }
        }
    }

    /**
     * 输入底部脚本样式
     * @return void
     */
    public function outputFooters(): void
    {
        foreach ($this->beforeOutputFooters as $item) {
            if (is_callable($item)) {
                $item();
            }
        }
        $this->sortByWeight($this->footerFiles);
        foreach ($this->footerFiles as $file) {
            $this->includeAssetsFile($file[0], $file[2], $file[3]);
        }
        foreach ($this->footerContents as $content) {
            if ($content[0] === 'css') {
                echo '<style type="text/css">', $content[1], '</style>';
            } elseif ('js' === $content[0]) {
                if (str_starts_with($content[1], '<script') && str_ends_with($content[1], '</script>')) {
                    echo $content[1];
                } else {
                    echo '<script type="text/javascript">', $content[1], '</script>';
                }
            }
        }
        $this->appendTemplateJs();
        foreach ($this->afterOutputFooters as $item) {
            if (is_callable($item)) {
                $item();
            }
        }
    }

    /**
     * 检查被引用的文件是否为本地文件
     * @param string $file
     * @param string $type
     * @return bool
     */
    public function includeAssetsFile(string $file, string $type = '', bool $local = false): bool
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
                Logger::warning('不被支持的资源文件', ['file' => $file]);
                return false;
            }
        }
        // 如果是本地文件，则优先检查是否有 min 文件
        if ($local) {
            $file = self::getAssetPath($file, tryMin: $this->min, must: false); // 检查是否存在压缩文件
            if (empty($file)) {
                return false;
            }
        }
        if ('css' == $type) {
            if ($local) {
                echo '<style type="text/css">';
                require_once $file;
                echo '</style>';
            } else {
                echo '<link rel="stylesheet" type="text/css" href="', $file, '">';
            }
        } elseif ('js' == $type) {
            if ($local) {
                echo '<script>';
                require_once $file;
                echo '</script>';
            } else {
                echo '<script src="', $file, '"></script>';
            }
        } else {
            Logger::warning('不被支持的资源文件类型', [
                'file' => $file,
                'type' => $type
            ]);
            return false;
        }
        $this->hasImports[] = $file;
        return true;
    }


    /**
     * 一个兼容方法，因为 ai 总是生成这个方法
     * @param mixed $text
     * @return mixed
     */
    public function text(mixed $text)
    {
        return $text;
    }

    /**
     * 获取 request post 中的数据
     * @param string $name
     * @param mixed $default 默认值
     * @param string $filter 过滤方式
     * @return mixed
     */
    public function pickPost(string $name, mixed $default = '', string $filter = ''): mixed
    {
        return AppService::request()->getPost($name, $filter, $default);
    }

    /**
     * @var string 指定要加载的脚本
     */
    public string $pickName = '';

    /**
     * 如果当前模板下存在着同名 js 文件，则引入它；比如你的模板为 add.phtml，如果存在 add.js 则会引入它
     * @return bool
     */
    public function appendTemplateJs(): bool
    {
        $theme = AppService::context()->theme;
        $pickName = $this->pickName ?: AppService::context()->getPickView();
        $jsFile = join(
                '/',
                $theme
                    ? [AppService::context()->getViewDIR(), $theme, $pickName]
                    : [AppService::context()->getViewDIR(), $pickName]
            ) . '.js';
        return AppService::html()->includeAssetsFile($jsFile, 'js');
    }


    /**
     * 检查资源路径
     * @param string $file 文件路径
     * @param bool $tryMin 是否尝试使用 .min.js/.min.css
     * @return string 返回文件路径
     */
    public static function getAssetPath(string $file, bool $tryMin = true, bool $must = true): string
    {
        if (!file_exists($file)) {
            if ($must) {
                throw new BusinessException('资源文件不存在：' . basename($file), [
                    'file' => $file
                ]);
            }
            return '';
        }
        if (str_ends_with($file, '.min.js') || str_ends_with($file, '.min.css')) {
            return $file;
        }
        if ($tryMin) {
            $minFile = str_replace(['.css', '.js'], ['.min.css', '.min.js'], $file);
            return file_exists($minFile) ? $minFile : $file;
        }
        return $file;
    }
}