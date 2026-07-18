<?php

namespace Phax\Support;

use Phax\Foundation\AppService;


/**
 * 处理 uri 请求，请求地址格式为 /语言/api/[模块|应用]
 */
class Router
{
    /**
     * 多模块标识
     */
    public static string $moduleKeyword = 'm';
    public static string $modulePrefix = '/m/';
    /**
     * 前端项目标识
     */
    public static string $projectKeyword = 'p';
    public static string $projectPrefix = '/p/';

    /**
     * 默认的语言参数匹配表达式
     * @var string
     */
    public static string $languageRule = '/{language:[a-zA-Z]{2}(-[a-zA-Z]{2})?}';

    /**
     * @param string $path
     * @return array{sw:bool,language:string,api:bool,project:bool,module:bool,path:string,mapurl:string}
     */
    public static function pathMatch(string $path = ''): array
    {
        $matches = [
            'language' => '',
            'api' => false,
            'project' => false,
            'module' => false,
            'mapurl' => $path,
        ];
        preg_match('|^/[a-zA-Z]{2}(-[a-zA-Z]{2})?/|', $path, $match);
        if (isset($match[0])) {
            $matches['language'] = trim($match[0], '/');
            $path = substr($path, strlen($matches['language']) + 1);
        }
        if (str_starts_with($path, '/api/')) {
            $matches['api'] = true;
            $path = substr($path, 4);
        }
        if (str_starts_with($path, self::$projectPrefix)) {
            $matches['project'] = true;
            $matches['path'] = substr($path, strlen(self::$projectPrefix));
        } elseif (str_starts_with($path, self::$modulePrefix)) {
            $matches['module'] = true;
            $matches['path'] = substr($path, strlen(self::$modulePrefix));
        } else {
            $matches['path'] = $path;
        }

        $matches['path'] = trim($matches['path'], '/');
        return $matches;
    }

    /**
     * 获取请求地址的路径
     * @param string $requestURI
     * @return string
     */
    public static function getURLPath(string $requestURI): string
    {
        // 去掉请求参数
        $index = strpos($requestURI, '?');
        return $index === false ? $requestURI : substr($requestURI, 0, $index);
    }

    /**
     * 格式化控制器/操作名称
     * @param string $name refreshNode, refresh-node, refresh_node, refreshNodeAction, refreshNodeController
     * @return string refreshNode
     */
    public static function formatNodeName(string $name, bool $lcfirst = true): string
    {
        if (str_ends_with($name, '.php')) {
            $name = substr($name, 0, -4);
        }
        if (str_ends_with($name, 'Action')) {
            $name = substr($name, 0, -6);
        } elseif (str_ends_with($name, 'Controller')) {
            $name = substr($name, 0, -10);
        }
        return self::formatName($name, $lcfirst);
    }

    /**
     * 格式化命名
     * @param string $name refreshNode, refresh-node, refresh_node, RefreshNode
     * @param bool $lcfirst 首字母是否小写，默认是
     * @return string refreshNode
     */
    public static function formatName(string $name, bool $lcfirst = true): string
    {
        $name = str_replace(['-', '_', ' '], '-', $name);
        if (str_contains($name, '-')) {
            $name = AppService::helper()->camelize($name, '-', true);
        }
        return $lcfirst ? lcfirst($name) : $name;
    }


    // 统一格式 someCtrl/someAction
    public static function formatPickView($controller, $action): string
    {
        $cName = self::formatNodeName($controller);
        $aName = self::formatNodeName($action);
        return $cName . '/' . $aName;
    }
}