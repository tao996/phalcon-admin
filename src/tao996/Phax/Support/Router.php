<?php

namespace Phax\Support;

use Phax\Support\Facade\MyHelperFacade;


/**
 * 处理 uri 请求，请求地址格式为 /sw/语言/api/[模块|应用]
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
     * swoole 或者 workerman 标识
     * @var string
     */
    public static string $swKeyword = 'sw';
    /**
     * 默认的语言参数匹配表达式
     * @var string
     */
    public static string $languageRule = '/{language:[a-zA-Z]{2}(-[a-zA-Z]{2})?}';

    /**
     * @param string $path
     * @return array{sw:bool,language:string,api:bool,project:bool,module:bool,path:string}
     */
    public static function pathMatch(string $path = ''): array
    {
        $matches = [
            'sw' => false,
            'language' => '',
            'api' => false,
            'project' => false,
            'module' => false,
        ];
        if (str_starts_with($path, '/' . self::$swKeyword)) {
            $matches['sw'] = true;
            $path = substr($path, strlen(self::$swKeyword) + 1);
        }
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
        $info = self::pathMatch($path);
//        ddd($path, $info);
        if ($info['module']) {
            $urlElements = empty($info['path']) ? [] : explode('/', $info['path']);

            $hasModuleName = isset($urlElements[0]);
            $hasControllerName = isset($urlElements[1]);
            $hasActionName = isset($urlElements[2]);

            $moduleName = self::formatName($urlElements[0] ?? 'index');
            $first_part = $urlElements[1] ?? 'index';
            $second_part = $urlElements[2] ?? 'index';

            $data = [
                'pattern' => self::$modulePrefix . (!$hasModuleName ? '' : ':module'),
                'paths' => ['module' => $moduleName, 'controller' => $first_part, 'action' => $second_part],
                'pathsname' => ['module' => $moduleName, 'controller' => $first_part, 'action' => $second_part],
                'namespace' => 'App\Modules\\' . $moduleName . '\Controllers',
                'viewpath' => PATH_APP . 'Modules/' . $moduleName . '/views',
                'module' => PATH_APP . 'Modules/' . $moduleName . '/Module.php',
                'name' => $moduleName,
            ];
//            ddd($path,$info['path'],$urlElements,[$moduleName,$controllerName,$actionName]);
            switch (count($urlElements)) {
                case 0:
                    break;
                case 1: // m1 或者 m1.subM
                    $data['paths']['module'] = 1;
                    self::subMultipleModulesRoute($moduleName, 'index', $data);
                    break;
                case 2: // m1/c1 或者 m1.subM/c1
                    $data['pattern'] .= '/:controller';
                    $data['paths']['module'] = 1;
                    $data['paths']['controller'] = 2;
                    self::subMultipleModulesRoute($moduleName, $first_part, $data);
                    break;
                case 3: // m1/c1/a1
                    $data['pattern'] .= '/:controller/:action';
                    $data['paths'] = ['module' => 1, 'controller' => 2, 'action' => 3];
                    self::subMultipleModulesRoute($moduleName, $first_part, $data);
                    break;
                default: // a/b/c/d
                    $data['pattern'] .= '/:controller/:action/:params';
                    $data['paths'] = ['module' => 1, 'controller' => 2, 'action' => 3, 'params' => 4];
                    self::subMultipleModulesRoute($moduleName, $first_part, $data);
                    break;
            }
        } else {
            // 单模块
            $data = [
                'pattern' => '/',
                'paths' => ['controller' => 'index', 'action' => 'index'],
                'namespace' => 'App\Http\Controllers',
                'viewpath' => PATH_APP . 'Http/views',
            ];
            if ($info['project']) {
                if (empty($info['path'])) {
                    $data['project'] = $options['project'];
                } else {
                    $urlElements = explode('/', $info['path']);
                    $data['project'] = $urlElements[0];
                    $info['path'] = ltrim(substr($info['path'], strlen($data['project']) + 1), '/');
                }
            }
            if (empty($data['project']) && $options['project']) {
                $data['project'] = $options['project'];
            }
            $urlElements = empty($info['path']) ? [] : explode('/', $info['path']);
            $first_part = $urlElements[0] ?? 'index';
            $second_part = $urlElements[1] ?? 'index';
            $data['pathsname'] = ['controller' => $first_part, 'action' => $second_part];
            if (!empty($data['project'])) {
                $data['namespace'] = 'App\Projects\\' . $data['project'] . '\Controllers';
                $data['viewpath'] = '/var/www/App/Projects/' . $data['project'] . '/views';
            }
            switch (count($urlElements)) {
                case 0:
                    break;
                case 1:
                    $data['pattern'] = '/:controller';
                    $data['paths']['controller'] = 1;
                    self::subAppControllerRoute($first_part, $data, $data['project'] ?? '');
                    break;
                case 2:
                    // 可能是 controller/action 或者 sub.controller/action
                    $data['pattern'] = '/:controller/:action';
                    $data['paths'] = ['controller' => 1, 'action' => 2,];
                    $data['pathsname'] = ['controller' => $first_part, 'action' => $second_part];
                    self::subAppControllerRoute($first_part, $data, $data['project'] ?? '');
                    break;
                default:
                    /*
                     * url 可能是
                     * controller/action/params
                     * sub.controller/action/params
                     * m/sub.controller/action
                     * m/sub.controller/action/params
                     */
                    $data['pattern'] = '/:controller/:action/:params';
                    $data['paths'] = ['controller' => 1, 'action' => 2, 'params' => 3];
                    // sub.controller/action/params
                    if (str_contains($first_part, '.')) {
                        self::subAppControllerRoute($first_part, $data, $data['project'] ?? '');
                    } elseif (str_contains($second_part, '.')) { // 子模块
                        $data['namespace'] = str_replace(
                            '\\Controllers',
                            '\\A0\\' . $first_part . '\\Controllers',
                            $data['namespace']
                        );
                        $data['viewpath'] = str_replace(
                            '/views',
                            '/A0/' . $first_part . '/views',
                            $data['viewpath']
                        );
                        $data['subm'] = $first_part;
                        $data['pathsname']['action'] = $urlElements[2] ?? 'index';
                        self::subAppControllerRoute($second_part, $data, $data['project'] ?? '');
                        if (count($urlElements) == 3) {
                            $data['pattern'] = '/:controller/:action';
                            unset($data['paths']['params']);
                        }
                    }
                    break;
            }
        }


        // /cn/m/xxx, /cn/api/m/xxx, /cn/p/xxx, /cn/api/p/xxx
        if ($info['project']) {
            $data['pattern'] = '/p/' . $data['project'] . $data['pattern'];
        }
        if ($info['api']) {
            $data['pattern'] = '/api' . $data['pattern'];
        }

        if ($info['language']) {
            $data['pattern'] = Router::$languageRule . $data['pattern'];
            foreach ($data['paths'] as $key => $value) {
                if (is_integer($value)) {
                    $data['paths'][$key] = $value + 1;
                }
            }
        }
        if ($info['sw']) {
            $data['pattern'] = '/' . self::$swKeyword . $data['pattern'];
        }
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
            // "/var/www/App/Modules/m1.m2/views" => "/var/www/App/Modules/m1/A0/m2/views"
            $data['viewpath'] = str_replace('.', '/A0/', $data['viewpath']);
            // "/var/www/App/Modules/m1.m2/Module.php" => '/var/www/App/Modules/m1/Module.php'
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
     * @return array{pattern:string,paths:array{module:string,controller:string,action:string},pathsname:array{module:string,controller:string,action:string},namespace:string,viewpath:string,project:string,route:string,pickview:string}
     * @throws \Exception
     */
    public static function analysisWithURL(string $requestURI, array $options = []): array
    {
        // 去掉请求参数
        $index = strpos($requestURI, '?');
        $requestURI = $index === false ? $requestURI : substr($requestURI, 0, $index);
        $config = self::analysisRoutePath($requestURI, $options);

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
        if (str_ends_with($requestURI, '/')) {
            if (!str_ends_with($config['route'], '/')) {
                $config['route'] .= '/';
            }
        } else {
            $config['route'] = rtrim($config['route'], '/');
        }
        $config['pathsname']['controller'] = self::formatName($config['pathsname']['controller']);
        $config['pathsname']['action'] = self::formatName($config['pathsname']['action']);

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


    // 统一格式 someCtrl/someAction
    public static function formatPickView($controller, $action): string
    {
        $cName = self::formatNodeName($controller);
        $aName = self::formatNodeName($action);
        return $cName . '/' . $aName;
    }
}