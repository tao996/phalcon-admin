<?php

namespace Phax\Support\Facade;

use Phax\Foundation\Application;

class MyUrlFacade
{
    /**
     * <code>
     * static UrlInterface setBasePath(string $basePath)
     * static UrlInterface setBaseUri(string $uri)
     * static UrlInterface setStaticBaseUri(string $staticBaseUri)
     * static string getStaticBaseUri() // 域名，即 config('app.url')
     * static string getStatic(string|array $option) staticBaseUri+$option
     * static string getBaseUri()
     * static string getBasePath()
     * static string get($uri = null, $args = null, bool $local = null, $baseUri = null)
     * static string path(string $path = null)
     * </code>
     * @return mixed
     */
    public static function getFacadeObject():\Phalcon\Mvc\Url
    {
        return Application::di()->get('url');
    }

    private static function getOrigin(): string
    {
        return ltrim(self::getFacadeObject()->getStaticBaseUri(), '/');
    }
}