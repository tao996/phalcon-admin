<?php

namespace Phax\Helper;

use Phax\Support\Router;
use Phax\Utils\MyData;

class MyUrl
{

    /**
     * 在 php cli 下无法获取
     * @param \Phalcon\Http\Request|\Phalcon\Http\RequestInterface $request
     * @return string
     */
    public static function getRealOrigin(\Phalcon\Http\Request|\Phalcon\Http\RequestInterface $request): string
    {
        if ($request->hasServer('HTTP_X_FORWARDED_HOST') || $request->hasServer('HTTP_HOST')) {
            // Priority to X-Forwarded-Host for proxies/load balancers
            $host = $request->hasServer('HTTP_X_FORWARDED_HOST') ? $request->getServer(
                'HTTP_X_FORWARDED_HOST'
            ) : $request->getServer('HTTP_HOST');
        } else {
            // Fallback to server address if no forwarding headers
            $host = $request->getServer('SERVER_NAME');
        }

        $scheme = $request->hasServer('HTTPS')
        && (($request->getServer('HTTPS') == 'on') || ($request->getServer('HTTPS') == 1))
            ? 'https' : 'http';
        $port = $request->getServer('SERVER_PORT') != '80' && $request->getServer(
            'SERVER_PORT'
        ) != '443' ? ':' . $request->getServer('SERVER_PORT') : '';

        return "$scheme://$host$port";
    }

    /**
     * 拼接一个地址
     * @param array{origin:string,prefix:string,language:string,api:bool, module:bool,project:bool,path:string, query:array|string} $options
     * @return string
     */
    public static function createWith(array $options = []): string
    {
        $items = [];
        if (!empty($options['prefix']) && is_string($options['prefix'])) {
            $items[] = $options['prefix'];
        }
        if (!empty($options['language']) && is_string($options['language'])) {
            $items[] = $options['language'];
        }

        if (!empty($options['api'])) {
            $items[] = 'api';
        }
        if (!empty($options['module'])) {
            $items[] = Router::$modulePrefix;
        }
        if (!empty($options['project'])) {
            $items[] = Router::$projectPrefix;
        }
        $path = MyData::getString($options, 'path');
        if ($items) {
            $url = '/' . join('/', $items) . '/' . ltrim($path, '/');
        } else {
            $url = '/' . ltrim($path, '/');
        }
        if (!empty($options['query'])) {
            $q = is_array($options['query']) ? http_build_query($options['query']) : $options['query'];
            $url = str_contains($url, '?') ? $url . '&' . $q : $url . '?' . $q;
        }
        $origin = '';
        if (!empty($options['origin'])) {
            if (!is_string($options['origin'])) {
                throw new \Exception('options.origin must be string');
            }
            $origin = $options['origin'];
        }
        return $origin ? rtrim($origin, '/') . $url : $url;
    }

    /**
     * 生成多模块接口 URL 地址
     * <code>
     * MyUrlFacade::createMultiApiURL('m1/c1/a1',['name'=>'bibi','age'=>5]);
     * // http://localhost/api/m/m1/c1/a1?name=bibi&age=5
     * </code>
     * @param string $path
     * @param array $query
     * @return string
     * @throws \Exception
     */
    public static function createMultiApiURL(string $path, array $query = [], string|bool $origin = true): string
    {
        return self::createWith([
            'path' => $path,
            'query' => $query,
            'api' => true,
            'module' => true,
            'origin' => $origin,
        ]);
    }

    /**
     * 生成多模块 URL 地址
     * <code>
     *  MyUrlFacade::createMultiURL('m1/c1/a1',['name'=>'bibi','age'=>5]);
     *  // http://localhost/m/m1/c1/a1?name=bibi&age=5
     * </code>
     * @param string $path 路径
     * @param array $query 查询参数
     * @return string
     * @throws \Exception
     */
    public static function createMultiURL(string $path, array $query = [], string $origin = ''): string
    {
        return self::createWith([
            'origin' => $origin,
            'module' => true,
            'path' => $path,
            'query' => $query,
        ]);
    }

    /**
     * 生成单应用 URL 地址
     * @param string $path
     * @param array $query
     * @return string
     * @throws \Exception
     */
    public static function createAppURL(string $path, array $query = [], string $origin = ''): string
    {
        return self::createWith([
            'path' => $path,
            'query' => $query,
            'project' => true,
            'origin' => $origin
        ]);
    }

    /**
     * 生成单应用 api URL 地址
     * @param string $path
     * @param array $query
     * @return string
     * @throws \Exception
     */
    public static function createAppApiURL(string $path, array $query = [], string $origin = ''): string
    {
        return self::createWith([
            'path' => $path,
            'query' => $query,
            'api' => true,
            'project' => true,
            'origin' => $origin,
        ]);
    }


    /**
     * 检查给定网址的域名，是否在指定的域名列表中
     * https://www.php.net/manual/zh/url.constants.php#constant.php-url-host
     * <code>
     * $url = 'https://www.test.com/abc';
     * $hosts = ['www.test.com']
     * assertTrue(inHosts($ur, $hosts));
     * </code>
     * @param string $url 域名地址，会自动提取 PHP_URL_HOST
     * @param array $hosts 主机名列表
     * @return bool
     */
    public static function inHosts(string $url, array $hosts): bool
    {
        return in_array(parse_url($url, PHP_URL_HOST), $hosts);
    }
}