<?php

namespace Phax\Support;

use Phax\Support\Facade\MyHelperFacade;
use Phax\Utils\MyData;


/**
 * 处理 uri 请求，请求地址格式为 /语言/api/[模块|应用]
 */
class Router
{
    /**
     * 多模块标识
     */
    public static string $modulePrefix = 'm';
    /**
     * 前端项目标识
     */
    public static string $projectPrefix = 'p';
    /**
     * swoole or workerman prefix
     * @var string
     */
    public static string $swPrefix = 'sw';
    /**
     * 默认的语言参数匹配表达式
     * @var string
     */
    public static string $languageRule = '/{language:[a-z]{2}}';

    /**
     * 注意，not include ajax request，如果要判断是否 ajax，还需要 request()->isAjax()
     * @return bool
     */
    public static function isApiPath(string $url = ''): bool
    {
        if (empty($url)) {
            return false;
        }

        return str_starts_with(self::filterIfLanguage($url), '/api/');
    }

    /**
     * 判断是否为多模块
     * @param string $url
     * @return bool
     */
    public static function isMultipleModules(string $url = ''): bool
    {
        if (empty($url)) {
            return false;
        }
        $url = self::filterIfLanguage($url);
        return str_starts_with($url, '/api/' . self::$modulePrefix . '/')
            || str_starts_with($url, '/' . self::$modulePrefix . '/');
    }

    /**
     * 应用 src/app/Projects
     * @param string $url
     * @return bool
     */
    public static function isAppProject(string $url = ''): bool
    {
        if (empty($url)) {
            return false;
        }
        $url = self::filterIfLanguage($url);
        return str_starts_with($url, '/api/' . self::$projectPrefix . '/')
            || str_starts_with($url, '/' . self::$projectPrefix . '/');
    }

    /**
     * 获取应用名称
     * @param string $url
     * @param string $defProject 默认项目
     * @return string 去掉相关前缀及应用名称后剩余部分
     */
    public static function getProjectName(string $url, string $defProject): string
    {
        $path = substr($url, 3); // 去掉 /p 前缀
        if (empty($path)) {
            return $defProject;
        }
        $index = strpos($path, '/');
        if ($index === false) {
            return $path;
        }
        return substr($path, 0, $index);
    }

    /**
     * 如果是多语言地址，则过滤掉它
     * @param string $url
     * @return string
     */
    public static function filterIfLanguage(string $url): string
    {
        return self::hasLanguage($url) ? substr($url, 3) : $url;
    }

    /**
     * 判断是否为多语言
     * @param string $url
     * @return bool
     */
    public static function hasLanguage(string $url = ''): bool
    {
        if ($url === '') {
            return false;
        }
        return preg_match('|^/[a-z]{2}/|', $url);
    }

    /**
     * 获取匹配到的语言
     * @param string $url
     * @return string
     */
    public static function getMatchLanguage(string $url = ''): string
    {
        if ($url === '') {
            return false;
        }
        $url = self::filterOtherPrefix($url);
        preg_match_all('|^/([a-z]{2})/|', $url, $match);
        return isset($match[1][0]) && $match ? $match[1][0] : '';
    }

    /**
     * 获取语言
     * @param string $url 待分析的链接, 默认为 $_SERVER['REQUEST_URI']
     * @return string
     */
    public static function getLanguage(string $url = ''): string
    {
        return self::getMatchLanguage($url);
    }

    public static function isSwooleProjec(string $url): bool
    {
        return !empty($url) && str_starts_with($url, '/' . self::$swPrefix . '/');
    }

    public static function filterOtherPrefix(string $url = ''): string
    {
        if (self::isSwooleProjec($url)) {
            return substr($url, strlen(self::$swPrefix) + 1);// 斜杠要保留
        }
        return $url;
    }

    /**
     * 路由请求
     * @param string $path path 地址，注意不能带有 ?
     * @param array $options ['project'=>'默认的项目名称']
     * @throws \Exception
     */
    public static function analysisRoutePath(string $path, array $options = []): array
    {
        if (str_contains($path, '?')) {
            throw new \Exception('analysisRoute error: it should not contain "?" char');
        }
        $options = array_merge(['project' => ''], $options);

        if ($swooleProject = self::isSwooleProjec($path)) {
            $path = self::filterOtherPrefix($path);
        }
        // 检查语言
        $isLanguage = self::hasLanguage($path);
        if ($isLanguage) {
            $path = self::filterIfLanguage($path);
        }

        // 检查是否 api
        $isApi = self::isApiPath($path);
        if ($isApi) {
            $path = substr($path, 4);
        }

        $multiPrefix = '/' . self::$modulePrefix . '/';
        $projectPrefix = '/' . self::$projectPrefix . '/';

        $isMulti = str_starts_with($path, $multiPrefix);
        $isProject = str_starts_with($path, $projectPrefix);
//        dd($isMulti, $isProject, true);
        if ($isMulti) {
//            $url = rtrim($url, '/');
            $path = substr($path, strlen($multiPrefix) - 1); // 去掉多模块
            $blank = '/' === $path;
            if ($blank) {
                $path = '/index';
            }
            $urlElements = explode('/', $path);
            if (count($urlElements) >= 2 && end($urlElements) == "") {
                array_pop($urlElements);
                $path = rtrim($path, '/');
            }
            if (isset($urlElements[1])) {
                $urlElements[1] = self::formatName($urlElements['1']);
            }
            $data = [
                'pattern' => $multiPrefix . ($blank ? '' : ':module'),
                'paths' => ['module' => 'index', 'controller' => 'index', 'action' => 'index'],
                'pathsname' => ['module' => 'index', 'controller' => 'index', 'action' => 'index'],
                'namespace' => 'App\Modules\\' . $urlElements[1] . '\Controllers',
                'viewpath' => PATH_APP . 'Modules/' . $urlElements[1] . '/views',
                'module' => PATH_APP . 'Modules/' . $urlElements[1] . '/Module.php',
                'name' => $urlElements[1],
            ];
//            dd(__FILE__,substr_count($path, '/'));
//            dd(__LINE__,$url,substr_count($url, '/'));
            switch (substr_count($path, '/')) {
                case 1: // /m1 或者 /m1.subM
                    if (!$blank) {
                        $data['paths']['module'] = 1;
                        $data['pathsname']['module'] = $urlElements[1];
                        self::subMultipleModulesRoute($urlElements[1], 'index', $data);
                    }
                    break;
                case 2: // /m1/c1 或者 /m1.subM/c1
                    $data['pattern'] .= '/:controller';
                    $data['paths']['module'] = 1;
                    $data['paths']['controller'] = 2;

                    $data['pathsname']['module'] = $urlElements[1];
                    $data['pathsname']['controller'] = $urlElements[2];
                    self::subMultipleModulesRoute($urlElements[1], $urlElements[2], $data);
                    break;
                case 3: // /m1/c1/a1
                    $data['pattern'] .= '/:controller/:action';
                    $data['paths'] = ['module' => 1, 'controller' => 2, 'action' => 3];
                    $data['pathsname'] = [
                        'module' => $urlElements[1],
                        'controller' => $urlElements[2],
                        'action' => $urlElements[3]
                    ];
                    self::subMultipleModulesRoute($urlElements[1], $urlElements[2], $data);
                    break;
                default: // /a/b/c/d
                    $data['pattern'] .= '/:controller/:action/:params';
                    $data['paths'] = ['module' => 1, 'controller' => 2, 'action' => 3, 'params' => 4];
                    $data['pathsname'] = [
                        'module' => $urlElements[1],
                        'controller' => $urlElements[2],
                        'action' => $urlElements[3]
                    ];
                    self::subMultipleModulesRoute($urlElements[1], $urlElements[2], $data);
                    break;
            }
        } else {
            // 单模块
            $data = [
                'pattern' => '/',
                'paths' => ['controller' => 'index', 'action' => 'index'],
                'pathsname' => ['controller' => 'index', 'action' => 'index'],
                'namespace' => 'App\Http\Controllers',
                'viewpath' => PATH_APP . 'Http/views',
            ];
            if ($isProject) {
                $data['project'] = self::getProjectName($path, $options['project'] ?? '');
                if (empty($data['project'])) {
                    throw new \Exception('project name must not empty where url has p');
                }
                $path = substr($path, 3 + strlen($data['project'])) ?: '/';
            } elseif (!empty($options['project'])) {
                $data['project'] = $options['project'];
                $isProject = true;
            }
            // /m1/sub.c1/a1 将被切割成 ["", "m1", "sub.c1", "a1"]
            $urlElements = explode('/', $path);
//dd(__FILE__,$path,substr_count($path, '/'),$urlElements);
            switch (substr_count($path, '/')) {
                case 0:
                    break;
                case 1:
                    if ('/' != $path) {
                        $data['pattern'] = '/:controller';
                        $data['paths']['controller'] = 1;
                        $data['pathsname']['controller'] = $urlElements[1];
                    }
                    self::subAppControllerRoute($urlElements[1], $data, $data['project'] ?? '');

                    break;
                case 2:
                    // 可能是 /controller/action 或者 /sub.controller/action
                    $data['pattern'] = '/:controller/:action';
                    $data['paths'] = ['controller' => 1, 'action' => 2,];
                    $data['pathsname'] = ['controller' => $urlElements[1], 'action' => $urlElements[2]];
                    self::subAppControllerRoute($urlElements[1], $data, $data['project'] ?? '');

                    break;
                default:
                    /*
                     * url 可能是
                     * /controller/action/params
                     * /sub.controller/action/params
                     * /m/sub.controller/action 或者 /m/sub.controller/action/params
                     */
                    $data['pattern'] = '/:controller/:action/:params';
                    $data['paths'] = ['controller' => 1, 'action' => 2, 'params' => 3];
                    $data['pathsname'] = ['controller' => $urlElements[1], 'action' => $urlElements[2]];
                    if (str_contains($urlElements[1], '.')) {
                        self::subAppControllerRoute($urlElements[1], $data, $data['project'] ?? '');
                    } elseif (str_contains($urlElements[2], '.')) { // 子模块
                        $data['namespace'] = str_replace(
                            '\\Controllers',
                            '\\A0\\' . $urlElements[1] . '\\Controllers',
                            $data['namespace']
                        );
                        $data['viewpath'] = str_replace(
                            '/views',
                            '/A0/' . $urlElements[1] . '/views',
                            $data['viewpath']
                        );
                        $data['subm'] = $urlElements[1];
                        $data['pathsname']['action'] = $urlElements[3];
                        self::subAppControllerRoute($urlElements[2], $data, $data['project'] ?? '');
                        if (count($urlElements) == 4) {
                            $data['pattern'] = '/:controller/:action';
                            unset($data['paths']['params']);
                        }
                    } elseif ($isProject) {
                        $data['namespace'] = 'App\Projects\\' . $data['project'] . '\Controllers';
                        $data['viewpath'] = '/var/www/app/Projects/' . $data['project'] . '/views';
                    }
                    break;
            }
        }

        $data['isLanguage'] = $isLanguage;
        $data['isApi'] = $isApi;

        // /cn/m/xxx, /cn/api/m/xxx, /cn/p/xxx, /cn/api/p/xxx
        if ($isProject) {
            $data['pattern'] = '/p/' . $data['project'] . $data['pattern'];
        }
        if ($isApi) {
            $data['pattern'] = '/api' . $data['pattern'];
        }

        if ($isLanguage) {
            $data['pattern'] = self::$languageRule . $data['pattern'];
            foreach ($data['paths'] as $key => $value) {
                if (is_integer($value)) {
                    $data['paths'][$key] = $value + 1;
                }
            }
        }
        if ($swooleProject) {
            $data['pattern'] = '/' . self::$swPrefix . $data['pattern'];
//            $data['route'] = '/' . self::$swPrefix . $data['route'];
        }
//        ddd('analysisRoutePath', $data);
        return $data;
    }

    /**
     * 单应用默认首页/子目录
     * @param string $controller 如果为空则为默认首页
     * @param array $data
     * @return void
     */
    private static function subAppControllerRoute(string $controller, array &$data, string $project): void
    {
        $g = explode('.', $controller);
        $gc = count($g);
        if ($gc == 2) {
            $data['pathsname']['controller'] = $g[1];
            $data['namespace'] .= ('\\' . $g[0]);
            $data['subc'] = $g[0];
        } elseif ($gc > 2) {
            throw new \Exception('sub dir example: /c1/sub.a1/params');
        }

        if ($project) {
            $data['namespace'] = str_replace(
                'Http\Controller',
                'Projects\\' . $project . '\Controller',
                $data['namespace']
            );
            $data['viewpath'] = str_replace(
                'Http/views',
                'Projects/' . $project . '/views',
                $data['viewpath']
            );
        }
    }

    /**
     * 多模块的子模块/子目录功能
     * @return void
     */
    private static function subMultipleModulesRoute(string $module, string $controller, array &$data): void
    {
        $subModule = str_contains($module, '.'); // 子模块
        $subControl = str_contains($controller, '.'); // 子目录
        if ($subModule) {
            $m = explode('.', $module);
            if (count($m) != 2) {
                throw new \Exception('multi module with sub module example: /m/m1.m2/controller/action/params');
            }
//            $m[0] = MyData::formatName($m[0]);
//            $m[1] = MyData::formatName($m[1]);

            $data['pathsname']['module'] = $m[0];
            // "App\Modules\m1.m2\Controllers" => "App\Modules\m1\A0\m2\Controllers"
            $data['namespace'] = str_replace('.', '\A0\\', $data['namespace']);
            // "/var/www/app/Modules/m1.m2/views" => "/var/www/app/Modules/m1/A0/m2/views"
            $data['viewpath'] = str_replace('.', '/A0/', $data['viewpath']);
            // "/var/www/app/Modules/m1.m2/Module.php" => '/var/www/app/Modules/m1/Module.php'
            $data['module'] = str_replace($module, $m[0], $data['module']);
            $data['name'] = $m[0];
            $data['subm'] = $m[1];
            if (!$subControl) {
                return;
            }
            // /m/m1.ext/sub1.c2
//            dd($module, $controller, $data); // m1.ext, sub1.c2
        }

        // 子目录
        if ($subControl) {
            $g = explode('.', $controller);
            if (count($g) != 2) {
                throw new \Exception('multi module with sub dir example: /m/m1/ext.controller/action/params');
            }
            $data['pathsname']['controller'] = $g[1];
            $data['namespace'] .= ('\\' . $g[0]);
            $data['subc'] = $g[0];
        }
    }


    /**
     * 分析链接
     * @param string $requestURI 待处理的 URL
     * @param array $options 配置信息  ['project'=> 默认前端应用]
     * @return array{pattern:string,paths:array{module:string,controller:string,action:string},pathsname:array{module:string,controller:string,action:string},namespace:string,viewpath:string,project:string,isLanguage:bool,language:string,isApi:bool,route:string,pickview:string}
     * @throws \Exception
     */
    public static function analysisWithURL(string $requestURI, array $options = []): array
    {
        // 去掉请求参数
        $index = strpos($requestURI, '?');
        $requestURI = $index === false ? $requestURI : substr($requestURI, 0, $index);
        $config = self::analysisRoutePath($requestURI, $options);
        $config['language'] = self::getLanguage($requestURI);


        if (isset($config['subc'])) { // 子目录
            $config['pickview'] = $config['subc'] . '/' . self::formatPickView(
                    $config['pathsname']['controller'],
                    $config['pathsname']['action']
                );
        }

        // 多模块时注册模块
        $isMultipleModules = !empty($config['module']);
        if ($isMultipleModules) {
            $hasModule = file_exists($config['module']);
            $config['registerModules'] = [
                $config['name'] => [
                    'path' => $hasModule
                        ? $config['module']
                        : dirname(__DIR__) . '/Mvc/Module.php',
                    'className' => $hasModule
                        ? 'App\Modules\\' . $config['name'] . '\Module'
                        : 'Phax\Mvc\Module',
                ]
            ];
        }
        // 替换掉 route，将被应用到路由中
        $config['route'] = $config['pattern'];
        if (isset($config['subm'])) {
            if ($isMultipleModules) {
                $config['route'] = str_replace(':module', ':module\.' . $config['subm'], $config['route']);
            } else {
                $config['route'] = str_replace(':controller', $config['subm'] . '/:controller', $config['route']);
            }
        }
        if (isset($config['subc'])) {
            $config['route'] = str_replace(
                ':controller',
                $config['subc'] . '\.([a-zA-Z0-9\_\-]+)',
                $config['route']
            );
        }
//        dd($config);
        return $config;
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
            $name = MyHelperFacade::camelize($name, '-', true);
        }
        return $lcfirst ? lcfirst($name) : $name;
    }

    public static function formatControllerName(string $name): string
    {
        return ucfirst(self::formatName($name, false));
    }


    // 统一格式 someCtrl/someAction
    public static function formatPickView($controller, $action): string
    {
        $cName = self::formatNodeName($controller);
        $aName = self::formatNodeName($action);
        return $cName . '/' . $aName;
    }
}