<?php

namespace Phax\Utils;

class MyUrl
{

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