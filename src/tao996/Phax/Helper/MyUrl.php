<?php

namespace Phax\Helper;

use Phax\Support\Router;
use Phax\Utils\MyData;

class MyUrl
{
    /**
     * 拼接一个网站内部地址
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
            $items[] = Router::$moduleKeyword;
        }
        if (!empty($options['project'])) {
            $items[] = Router::$projectKeyword;
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