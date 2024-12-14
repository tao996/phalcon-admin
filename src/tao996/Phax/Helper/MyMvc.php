<?php

namespace Phax\Helper;

use Phalcon\Encryption\Security;
use Phalcon\Http\Response\Cookies;
use Phalcon\Mvc\View;
use Phax\Foundation\Route;
use Phax\Support\Config;
use Phax\Support\Exception\BlankException;
use Phax\Support\I18n\Translate;
use Phax\Support\Logger;
use Phax\Support\Router;
use Phax\Support\Validate;
use Phax\Utils\MyData;

class MyMvc
{
    // controller.action 返回的数据在视图中的前缀
    public static string $prefix = 'api';

    /**
     * 视图服务
     * @var View|mixed
     */
    private View $view;

    private Translate $translate;

    public array $viewData = [];

    public function __construct(public \Phalcon\Di\Di $di)
    {
        if (!$this->route()->isApiRequest()) {
            $this->view = $di->get('view');
            $this->view->setVar('vv', $this);
        }
        $this->translate = new Translate();
    }

    /**
     * 是否为演示环境
     * @return bool
     */
    public function isDemo(bool $must = false): bool
    {
        $rst = $this->config()->isDemo();
        if ($must && !$rst) {
            throw new \Exception('only support in demo mode');
        }
        return $rst;
    }

    /**
     * 是否开启了测试环境
     * @return bool
     */
    public function isTest(): bool
    {
        return $this->request()->getQuery('test', 'string', '') === 'on'
            && $this->config()->isTest();
    }

    public function config(): Config
    {
        return $this->di->get('config');
    }

    public function view(): View
    {
        return $this->view;
    }

    public function translate(): Translate
    {
        return $this->translate;
    }

    public function route(): Route
    {
        return $this->di->get('route');
    }

    public function eventsManager(): \Phalcon\Events\Manager
    {
        return $this->di->get('eventsManager');
    }

    public function helper(): \Phalcon\Support\HelperFactory
    {
        return $this->di->getShared('helper');
    }

    public function router(): \Phalcon\Mvc\Router|\Phalcon\Cli\Router
    {
        return $this->di->get('router');
    }

    public function validate(): Validate
    {
        if (!$this->di->has('validate')) {
            $this->di->setShared('validate', new Validate($this));
        }
        return $this->di->getShared('validate');
    }

    public function cookies(): Cookies
    {
        return $this->di->get('cookies');
    }

    public function request(): \Phalcon\Http\RequestInterface
    {
        return $this->di->get('request');
    }

    public function response(): \Phalcon\Http\ResponseInterface
    {
        return $this->di->get('response');
    }

    public function responseMimeType(array $kvHeaders,string $content)
    {
        if (IS_WORKER_WEB){
            $response = $this->response();
            foreach ($kvHeaders as $k => $v){
                $response->setHeader($k, $v);
            }
            $response->setContent($content);
        } else {
            foreach ($kvHeaders as $k => $v){
                header("$k: $v");
            }
            echo $content;
        }
        throw new BlankException();
    }

    public function db(): \Phalcon\Db\Adapter\Pdo\AbstractPdo
    {
        return $this->di->get('db');
    }

    public function pdo(): \PDO
    {
        return $this->di->get('pdo');
    }

    public function redis(): \Redis
    {
        return $this->di->get('redis');
    }

    public function cache(): \Phalcon\Cache\Cache
    {
        return $this->di->get('cache');
    }

    public function metadata(): \Phalcon\Mvc\Model\MetaData
    {
        return $this->di->get('modelsMetadata');
    }

    public function logger(): \Phalcon\Logger\Logger
    {
        return $this->di->get('logger');
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
     * 页面标题
     * @return string
     */
    public function htmlTitle(): string
    {
        $title = $this->get('html_title');
        if ($title) {
            return $title . ' - ' . $this->config()->path('app.title');
        } else {
            return $this->config()->path('app.title');
        }
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
     * 通常用于将 php 变量转为 js 布尔值
     * @param bool $condition
     * @return string
     */
    public function htmlBoolText(bool $condition): string
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

    public function postData(string $name, mixed $default = '')
    {
        return $this->di->get('request')->getPost($name, '', $default);
    }

    public function setVar(string $key, $value): static
    {
        $this->viewData[$key] = $value;
        return $this;
    }

    public function setVars(array $params): static
    {
        $this->viewData = array_merge($this->viewData, $params);
        return $this;
    }

    /**
     * 返回 Project 资源路径
     * @param string $project 应用名称
     * @param string $pathInView 资源在 views/ 目录下的路径
     * @return string
     */
    public function projectAsset(string $project, string $pathInView): string
    {
        return \Phax\Support\Config::$local_assets_origin."/pstatic/{$project}/{$pathInView}";
    }

    /**
     * 返回 Module 资源路径
     * @param string $module 模块名称
     * @param string $pathInView 资源在 views/ 目录下的路径
     * @return string
     */
    public function moduleAsset(string $module,string $pathInView):string
    {
        return \Phax\Support\Config::$local_assets_origin."/mstatic/{$module}/{$pathInView}";
    }

    /**
     * 设置视图数据，并对视图模板进行检查
     * @return void
     * @throws \Exception
     */
    public function doneViewResponse(): void
    {
        $this->route()->doneView();
        if ($pickview = $this->route()->pickView(false)) {
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

    /**
     * 生成一个 URL 地址
     * @param array{origin:string,prefix:string,language:bool,api:bool, module:bool,project:bool,path:string, query:array|string} $options
     * @return string
     */
    public function url(array $options): string
    {
        if (MyData::getBool($options, 'origin', true)) {
            $options['origin'] = $this->route()->origin();
        }
        $options['language'] = $this->route()->urlOptions['language'];
        $options['prefix'] = $this->route()->urlOptions['sw'] ? Router::$swKeyword : '';
        return MyUrl::createWith($options);
    }

    /**
     * 生成一个 module 请求链接
     * @param string $path 路径
     * @param array|bool $mixed 如果为 `true` 则表示 `api` 请求；<br>
     * 如果为 `false` 则表示`不需要 origin`；<br>
     * 如果为 `array`，则是请求参数
     * @return string
     */
    public function urlModule(string $path, array|bool $mixed = []): string
    {
        $options = [
            'path' => $path,
            'module' => true,
            'origin' => true,
        ];
        if ($mixed === true) {
            $options['api'] = true;
        } elseif ($mixed === false) {
            $options['origin'] = '';
        } elseif (!empty($mixed)) {
            $options['query'] = $mixed;
        }
        return $this->url($options);
    }

    /**
     * 生成一个普通链接地址
     * @param string $path 路径，必须以 / 开头
     * @param array $query 查询参数
     * @return string
     */
    public function urlWith(string $path, array $query = []): string
    {
        return $this->url(['path' => $path, 'query' => $query]);
    }

    /**
     * 通常生成生成/校验表单 token；对密码进行加密处理
     * Random 生成随机数据；Hash 数据加密
     * Token 用于防止 CSRF 攻击；
     * https://docs.phalcon.io/5.0/en/encryption-security#random
     * @return \Phalcon\Encryption\Security
     */
    public function security(): Security
    {
        return $this->di->get('security');
    }

    public function session(): \Phalcon\Session\ManagerInterface
    {
        return $this->di->get('session');
    }

    function dispatcher(): \Phalcon\Dispatcher\AbstractDispatcher
    {
        return $this->di->get('dispatcher');
    }


    /**
     * @param string $key
     * @param array $placeholders
     * @param string $defMessage
     * @return string
     * @throws \Exception
     */
    public function __(string $key, array $placeholders = [], string $defMessage = ''): string
    {
        static $load = null;
        if (is_null($load)) { // 首次使用，自动加载
            $load = true;
            $this->translate()->load();
        }
        return Translate::get($this->getLanguage(), $key, $placeholders, $defMessage);
    }

    public function getLanguage()
    {
        // 路由
        if ($language = $this->route()->getLanguage()) { // 网址中设置的语言
            return $language;
        }
        // 请求参数
        if ($this->di->has('request')) {
            if ($language = $this->request()->getQuery('language')) {
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
        return $this->config()->path('app.locale', 'en');
    }

}