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
    private View $view;

    public array $viewData = [];

    public function __construct(public MyMvc $mvc)
    {
        if ($this->mvc->di->has('route') && !$this->mvc->route()->isApiRequest()) {
            $this->view = $this->mvc->di->get('view');
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
}