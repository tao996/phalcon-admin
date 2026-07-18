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