<?php

namespace Phax\Helper;

class MyMvc
{

    public function __construct(public \Phalcon\Di\Di $di)
    {
    }

    public function html(): HtmlHelper
    {
        static $html = null;
        if (is_null($html)) {
            $html = new HtmlHelper($this);
        }
        return $html;
    }

    /**
     * 获取控制器 Action 所返回的值
     * @param string $path
     * @param mixed $default
     * @return mixed
     */
    public function pick(string $path, mixed $default = ''): mixed
    {
        return $this->html()->pick($path, $default);
    }

    /**
     * 获取 request post 中的数据
     * @param string $name
     * @param mixed $default
     * @param string $filter
     * @return mixed
     */
    public function pickPost(string $name, mixed $default = '', string $filter = ''): mixed
    {
        return $this->di->getShared('request')
            ->getPost($name, $filter, $default);
    }

}