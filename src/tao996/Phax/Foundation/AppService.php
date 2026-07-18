<?php

namespace Phax\Foundation;

use Phalcon\Encryption\Security;
use Phalcon\Http\Response\Cookies;
use Phalcon\Mvc\View;
use Phax\Foundation\Context\RouteMatchContext;
use Phax\Helper\HtmlHelper;
use Phax\Support\Config;
use Phax\Support\Exception\BlankException;
use Phax\Support\Exception\LogException;
use Phax\Utils\MyData;
use Phax\Utils\MyUrlBuilder;

class AppService
{
    public static function has(string $serviceName): bool
    {
        return Application::di()->has($serviceName);
    }

    public static function getShared(string $serverName)
    {
        return Application::di()->getShared($serverName);
    }

    public static function setShared(string $serviceName, $service): void
    {
        Application::di()->setShared($serviceName, $service);
    }

    public static function mustFirstSet(string $serviceName, $service): void
    {
        if (self::has($serviceName)) {
            if (IS_DEBUG) {
                ddd('服务已存在:' . $serviceName, debug_backtrace(limit: 5));
            }
            throw new BlankException('服务已存在:' . $serviceName);
        }
        Application::di()->setShared($serviceName, $service);
    }

    public static function getDi(): \Phalcon\Di\Di
    {
        return Application::di();
    }

    public static function view(): View
    {
        return Application::di()->getShared('view');
    }

    /**
     * @return Config
     */
    public static function config(): Config
    {
        return Application::di()->get('config');
    }


    public static function dispatcher(): \Phalcon\Dispatcher\AbstractDispatcher
    {
        return Application::di()->getShared('dispatcher');
    }


    /**
     * 调用 console 任务
     * @param string $path 路径，示例 p/demo/main
     * @return array
     */
    public static function console(string $path, bool $filter = true): array
    {
        $cmd = 'php ' . PATH_ROOT . 'artisan ' . $path;

        exec($cmd, $output, $result_code);
        if ($result_code === 0) {
            return $filter ? array_filter($output) : $output;
        } else {
            throw new LogException('执行控制台(' . $cmd . ')任务失败', [
                'result_code' => $result_code,
                'output' => $output,
            ]);
        }
    }

    public static function getLanguage()
    {
        // 路由
        if ($language = AppService::context()->language) { // 网址中设置的语言
            return $language;
        }
        // 请求参数
        if (Application::di()->has('request')) {
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
        if ($language = self::dispatcher()->getParam('language')) {
            return $language;
        }
        return self::config()->getString('app.locale', 'cn');
    }

    public static function request(): \Phalcon\Http\RequestInterface
    {
        return Application::di()->get('request');
    }

    public static function response(): \Phalcon\Http\ResponseInterface
    {
        return Application::di()->getShared('response');
    }

    public static function cache(): \Phalcon\Cache\Cache
    {
        return Application::di()->getShared('cache');
    }

    public static function session(): \Phalcon\Session\ManagerInterface
    {
        return Application::di()->getShared('session');
    }


    public static function cookies(): Cookies
    {
        return Application::di()->getShared('cookies');
    }

    /**
     * 通常生成生成/校验表单 token；对密码进行加密处理
     * Random 生成随机数据；Hash 数据加密
     * Token 用于防止 CSRF 攻击；
     * https://docs.phalcon.io/5.0/en/encryption-security#random
     * @return \Phalcon\Encryption\Security
     */
    public static function security(): Security
    {
        return Application::di()->getShared('security');
    }

    /**
     * 超级管理员 ID
     * @return array
     */
    public static function superAdminIds(): array
    {
        return self::config()->getArray('app.superAdmin');
    }

    public static function router(): \Phalcon\Mvc\Router|\Phalcon\Cli\Router
    {
        return Application::di()->getShared('router');
    }

    public static function context(): RouteMatchContext
    {
        return Application::di()->getShared('context');
    }


    public static function db(): \Phalcon\Db\Adapter\Pdo\AbstractPdo
    {
        return Application::di()->getShared('db');
    }


    public static function pdo(): \PDO
    {
        return Application::di()->getShared('pdo');
    }

    public static function logger(): \Phalcon\Logger\Logger
    {
        return Application::di()->getShared('logger');
    }


    public static function metadata(): \Phalcon\Mvc\Model\MetaData
    {
        return Application::di()->getShared('modelsMetadata');
    }

    /**
     * 生成一个 URL 地址
     * @param array{origin:string,prefix?:string,language?:bool,api?:bool, module?:bool,project?:bool,path?:string, query?:array|string} $options
     * @return string
     */
    public static function url(array $options): string
    {
        $builder = MyUrlBuilder::new();
        $builder->language(self::context()->language);

        if (!empty($options['api'])) {
            $builder->asApi();
        }

        $path = MyData::getString($options, 'path');
        if (!empty($options['module'])) {
            $builder->withModule(ltrim($path, '/'));
        } elseif (!empty($options['project'])) {
            $builder->withProject(ltrim($path, '/'));
        } else {
            $builder->path($path);
        }

        if (!empty($options['query'])) {
            $builder->queryParams($options['query']);
        }
        if (isset($options['origin'])) {
            if (is_string($options['origin']) && !empty($options['origin'])) {
                $builder->origin($options['origin']);
            } elseif ($options['origin']) {
                $builder->origin(self::context()->appOrigin());
            }
        } else {
            $builder->origin(self::context()->appOrigin());
        }

        return $builder->build();
    }

    /**
     * 快捷地生成一个可带参数的链接地址
     * @param string $path 路径，必须以 / 开头
     * @param array $query 查询参数
     * @return string
     */
    public static function urlWith(string $path, array $query = []): string
    {
        return self::url(['path' => $path, 'query' => $query]);
    }


    public static function redis(): \Redis
    {
        return Application::di()->getShared('redis');
    }

    /**
     * 是否开启了测试环境
     * @return bool
     */
    public static function isTest(): bool
    {
        return self::request()->getQuery('test', 'string', '') === 'on'
            && self::config()->getBoolean('app.test.open');
    }

    /**
     * 是否为演示环境
     * @return bool
     */
    public static function isDemo(bool $must = false): bool
    {
        $rst = self::config()->getBoolean('app.demo.open');
        if ($must && !$rst) {
            throw new \Exception('only support in demo mode');
        }
        return $rst;
    }


    public static function html(): HtmlHelper
    {
        return self::getLazyService('html', function () {
            return new HtmlHelper();
        });
    }


    public static function eventsManager(): \Phalcon\Events\Manager
    {
        return Application::di()->getShared('eventsManager');
    }


    /**
     * 用于返回一些特殊格式的数据，如图片
     * <pre>
     * // 输出图片
     * responseMimeType(['Content-Type' => 'image/jpeg'], $response->getContent())
     * </pre>
     * @param array $kvHeaders
     * @param string $content
     * @return mixed
     * @throws BlankException
     */
    public static function responseMimeType(array $kvHeaders, string $content): mixed
    {
        foreach ($kvHeaders as $k => $v) {
            header("$k: $v");
        }
        echo $content;
        throw new BlankException();
    }


    /**
     * 返回 Project 资源路径
     * @param string $project 应用名称
     * @param string $pathInView 资源在 views/ 目录下的路径
     * @return string
     */
    public static function projectAsset(string $project, string $pathInView): string
    {
        return "/pstatic/{$project}/{$pathInView}";
    }

    /**
     * 返回 Module 资源路径
     * @param string $module 模块名称
     * @param string $pathInView 资源在 views/ 目录下的路径
     * @return string
     */
    public static function moduleAsset(string $module, string $pathInView): string
    {
        return "/mstatic/{$module}/{$pathInView}";
    }

    /**
     * 生成一个 module 请求链接
     * @param string $path 路径
     * @param array|bool $mixed 如果为 `true` 则表示 `api` 请求；<br>
     * 如果为 `false` 则表示`不需要 origin`；<br>
     * 如果为 `array`，则是请求参数
     * @return string
     */
    public static function urlModule(string $path, array|bool $mixed = []): string
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
            if (is_array($mixed)) {
                $mixed = array_filter($mixed, function ($v) {
                    return $v != '' && $v != 'null' && $v != '0' && $v != 'undefined';
                });
            }
            $options['query'] = $mixed;
        }
        return self::url($options);
    }


    /**
     * 重新整理参数，通常用于 `admin.table.with({url: prefix, query:})` 中
     * @return array
     */
    public static function queryParams(): array
    {
        $query = [];
        foreach (self::request()->getQuery() as $k => $v) {
            if ($k != '_url' && $v != '' && $v != 'null' && $v != '0' && $v != 'undefined') {
                $query[$k] = $v;
            }
        }
        return $query;
    }

    /**
     * 生成一个 project 请求链接
     * @param string $path 路径
     * @param array|bool $mixed 如果为 `true` 则表示 `api` 请求；<br>
     * 如果为 `false` 则表示`不需要 origin`；<br>
     * 如果为 `array`，则是请求参数
     * @return string
     */
    public static function urlProject(string $path, array|bool $mixed = []): string
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


    /**
     * 检测是否为移动端访问
     * 支持 UA 检测和 ?mobile=1 参数覆盖
     * @return bool
     */
    public static function isMobile(): bool
    {
        // 允许通过 URL 参数强制指定
        $mobileParam = self::request()->getQuery('mobile', 'int', -1);
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

    public static function isJsonBodyRequest(): bool
    {
        return self::request()->getQuery('data', 'string') === 'jsonbody';
    }

    public static function crypt(): \Phalcon\Encryption\Crypt
    {
        return self::getLazyService('crypt', function () {
            $data = self::config()->getArray('app.crypt');
            $crypt = new \Phalcon\Encryption\Crypt();
            $crypt->setPadding(MyData::getInt($data, 'padding'));
            $crypt->setKey(MyData::getString($data, 'key'));
            $crypt->setCipher(MyData::getString($data, 'cipher'));
            return $crypt;
        });
    }

    public static function helper(): \Phalcon\Support\HelperFactory
    {
        return self::getLazyService('helper', function () {
            return new \Phalcon\Support\HelperFactory();
        });
    }

    /**
     * 统一的懒加载注册中心
     */
    public static function getLazyService(string $name, callable $resolver)
    {
        if (!self::has($name)) {
            self::getDi()->setShared($name, $resolver);
        }
        return self::getDi()->getShared($name);
    }

    /**
     * 直接打印 json 数据，并结束程序
     * @param array $data
     * @return never
     */
    public static function echoJsonData(array $data): never
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

}