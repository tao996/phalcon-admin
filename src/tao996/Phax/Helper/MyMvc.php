<?php

namespace Phax\Helper;

use Phalcon\Encryption\Security;
use Phax\Foundation\AppService;

class MyMvc
{
    private HtmlHelper|null $_html = null;

    protected string $html_helper_class = HtmlHelper::class;

    public function __construct(public \Phalcon\Di\Di $di)
    {
    }

    public function html(): HtmlHelper
    {
        if (empty($this->_html)) {
            $this->_html = new $this->html_helper_class($this);
        }
        return $this->_html;
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


    /**
     * 生成一个 project 请求链接
     * @param string $path 路径
     * @param array|bool $mixed 如果为 `true` 则表示 `api` 请求；<br>
     * 如果为 `false` 则表示`不需要 origin`；<br>
     * 如果为 `array`，则是请求参数
     * @return string
     */
    public function urlProject(string $path, array|bool $mixed = []): string
    {
        $options = [
            'path' => $path,
            'project' => true,
            'origin' => true,
        ];
        if ($mixed === true) {
            $options['api'] = true;
        } elseif ($mixed === false) {
            $options['origin'] = false;
        } elseif (!empty($mixed)) {
            $options['query'] = $mixed;
        }
        return AppService::url($options);
    }

    public function session(): \Phalcon\Session\ManagerInterface
    {
        return $this->di->getShared('session');
    }

    function dispatcher(): \Phalcon\Dispatcher\AbstractDispatcher
    {
        return $this->di->getShared('dispatcher');
    }

    public function getLanguage()
    {
        // 路由
        if ($language = AppService::route()->getLanguage()) { // 网址中设置的语言
            return $language;
        }
        // 请求参数
        if ($this->di->has('request')) {
            if ($language = AppService::request()->getQuery('language')) {
                return $language;
            }
        }
        // cookies 会导致每次请求都生成一个 cookies
//        if ($this->di->has('cookies')) {
//            if ($language = $this->cookies()->get('language')->getValue('string')) {
//                $this->route()->routerOptions['language'] = $language;
//                return $language;
//            }
//        }
//        $lang = $this->di->get('request')->getBestLanguage(); // zh-CN
        // 路由中有语言请求参数，配置设置
        if ($language = $this->dispatcher()->getParam('language')) {
            return $language;
        }
        return AppService::config()->getString('app.locale', 'en');
    }

    /**
     * 调用 console 任务
     * @param string $path 路径，示例 p/demo/main
     * @return array
     */
    public function console(string $path, bool $filter = true): array
    {
        $cmd = 'php ' . PATH_ROOT . 'artisan ' . $path;

        exec($cmd, $output, $result_code);
        if ($result_code === 0) {
            return $filter ? array_filter($output) : $output;
        } else {
            throw new \Exception('failed:' . $cmd);
        }
    }

    /**
     * 检测是否为移动端访问
     * 支持 UA 检测和 ?mobile=1 参数覆盖
     * @return bool
     */
    public function isMobile(): bool
    {
        // 允许通过 URL 参数强制指定
        $mobileParam = AppService::request()->getQuery('mobile', 'int', -1);
        if ($mobileParam >= 0) {
            return (bool)$mobileParam;
        }
        // UA 检测
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if ($ua) {
            $keywords = ['Mobile', 'Android', 'iPhone', 'iPad', 'iPod', 'Windows Phone', 'Opera Mini', 'IEMobile'];
            foreach ($keywords as $kw) {
                if (str_contains($ua, $kw)) {
                    return true;
                }
            }
        }
        return false;
    }
}