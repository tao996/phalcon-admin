<?php

namespace EasyTiktok\Kernel;

class HttpClient
{
    public static function sandbox(\EasyWeChat\Kernel\Contracts\Config $config)
    {
        return $config->get('sandbox', 0);
    }

    public static function create(int $sandbox): \Symfony\Contracts\HttpClient\HttpClientInterface
    {
        return \Symfony\Component\HttpClient\HttpClient::create(['base_uri' => self::defaultBaseUri($sandbox)]);
    }

    public static function defaultBaseUri(int $sandbox): string
    {
        return $sandbox ? 'https://open-sandbox.douyin.com/' : 'https://developer.toutiao.com/';
    }
}