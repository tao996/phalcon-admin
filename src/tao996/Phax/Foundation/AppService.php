<?php

namespace Phax\Foundation;

use App\Modules\tao\Helper\LimitRateHelper;
use Phalcon\Encryption\Security;
use Phalcon\Http\Request;
use Phax\Helper\MyUrlBuilder;
use Phax\Support\Config;
use Phax\Utils\MyData;

class AppService
{
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

    public static function cache(): \Phalcon\Cache\Cache
    {
        return Application::di()->getShared('cache');
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


    public static function db(): \Phalcon\Db\Adapter\Pdo\AbstractPdo
    {
        return Application::di()->getShared('db');
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


    public static function limitRate(string $action, int $userId = 0): LimitRateHelper
    {
        return new LimitRateHelper(self::redis(), $action, $userId);
    }
}