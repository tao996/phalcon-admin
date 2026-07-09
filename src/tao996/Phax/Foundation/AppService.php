<?php

namespace Phax\Foundation;

use Phalcon\Encryption\Security;
use Phalcon\Http\Request;
use Phalcon\Http\Response\Cookies;
use Phalcon\Mvc\View;
use Phax\Helper\HtmlHelper;
use Phax\Helper\MyUrlBuilder;
use Phax\Support\Config;
use Phax\Support\Exception\BlankException;
use Phax\Utils\MyData;

class AppService
{
    public static function has(string $serviceName): bool
    {
        return Application::di()->has($serviceName);
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

    public static function request(): Request
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
        return self::config()->getSuperAdminIds();
    }


    public static function route(): Route
    {
        return Application::di()->getShared('route');
    }


    public function router(): \Phalcon\Mvc\Router|\Phalcon\Cli\Router
    {
        return Application::di()->getShared('router');
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
     * @param array{origin:string,prefix:string,language:bool,api:bool, module:bool,project:bool,path:string, query:array|string} $options
     * @return string
     */
    public static function url(array $options): string
    {
        $builder = MyUrlBuilder::new();

        $builder->language(self::route()->urlOptions['language']);

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
                $builder->origin(self::route()->appOrigin());
            }
        } else {
            $builder->origin(self::route()->appOrigin());
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
            && self::config()->isTest();
    }

    /**
     * 是否为演示环境
     * @return bool
     */
    public static function isDemo(bool $must = false): bool
    {
        $rst = self::config()->isDemo();
        if ($must && !$rst) {
            throw new \Exception('only support in demo mode');
        }
        return $rst;
    }


    public static function html(): HtmlHelper
    {
        static $obj = null;
        if (is_null($obj)) {
            $obj = new HtmlHelper(); // TODO 暂时有问题
        }
        return $obj;
    }


    public static function eventsManager(): \Phalcon\Events\Manager
    {
        return Application::di()->getShared('eventsManager');
    }


    public static function helper(): \Phalcon\Support\HelperFactory
    {
        return Application::di()->getShared('helper');
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
}