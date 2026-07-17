<?php

namespace Phax\Foundation\Context;

use Phax\Foundation\AppService;
use Phax\Foundation\Route;
use Phax\Support\Router;

class RouteMatchContext
{
    public string $theme = '';
    /**
     * 匹配位置, `$pathsname` 中占位符的顺序
     * @var array|string[]|int[]
     */
    public array $paths = [
        'controller' => '',
        'action' => '',
    ];
    /**
     * 匹配占位符 :module, :controller, :action, :params
     * @var array|string[]
     */
    public array $pathsname = [
        'controller' => 'index',
        'action' => 'index'
    ];
    /**
     * 子模块名称
     * @var string
     */
    public string $subm = '';
    /**
     * 子目录名称
     * @var string
     */
    public string $subc = '';
    /**
     * 路由注册信息
     * @var array
     */
    public array $registerModules = [];
    /**
     * $pattern 是由请求路径分析得到的，route 还需要进一步转换
     * @var string 用于注册路由
     */
    public string $route = '';
    /**
     * @var string 请求的路径
     */
    public string $mapurl = '';

    private string $pickview = '';

    public function __construct(
        public string $pattern = '',
        public string $namespace = '',
        public string $viewpath = '',
        public string $project = '',
        /**
         * @var string Module.php 路径
         */
        public string $modulePath = '',
        /**
         * @var string 模块或项目名称
         */
        public string $name = '',
        /**
         * @var string 请求地址
         */
        public string $requestURI = '',

        /**
         * @var string 请求路由的路径
         */
        public string $path = '',
        public string $language = '',
        public bool   $isApi = false,
        public bool   $isProject = false,
        public bool   $isModule = false,


        public string $mainView = '',

        public bool   $loadDefault = false,
    )
    {
        $this->mapurl = $this->path;
    }

    public static function with(string $requestURI, bool $loadDefault = false): RouteMatchContext
    {
        $path = Router::getURLPath($requestURI);
        $obj = new RouteMatchContext(requestURI: $requestURI, path: $path,
            loadDefault: $loadDefault);
        $obj->doWithContext();
        return $obj;
    }

    public function loadDefaultProject()
    {
        $config = AppService::config();
        $project = $config->getString('app.project');
        $this->updateProject($project);

        $defaultApp = $config->getArray('app.defaultApp');

        if (!empty($defaultApp['namespace'])) {
            $this->namespace = $defaultApp['namespace'];
        }
        if (empty($defaultApp['viewpath'])) {
            if ($this->namespace != 'App\\Http\\Controllers') {
                if (!str_ends_with($this->namespace, '\\Controllers')) {
                    throw new \Exception('自定义命名空间必须以 \\Controllers 结尾');
                }
                if (str_starts_with($this->namespace, 'App\\Modules\\')) {
                    $cc = explode('\\', $this->namespace);
                    $this->viewpath = PATH_APP_MODULES . $cc[2] . '/views';
                } elseif (str_starts_with($this->namespace, 'App\\Projects\\')) {
                    $cc = explode('\\', $this->namespace);
                    $this->viewpath = PATH_APP_PROJECTS . $cc[2] . '/views';
                }
            }
        }
    }

    public function updateProject(string $project): void
    {
        if (!empty($project)) {
            $this->isProject = true;
            $this->name = $project;
            $this->namespace = 'App\\Projects\\' . $project . '\\Controllers';
            $this->viewpath = PATH_APP_PROJECTS . $project . DIRECTORY_SEPARATOR . 'views';
        }
    }

    public function doWithContext(): void
    {
        preg_match('|^/[a-zA-Z]{2}(-[a-zA-Z]{2})?/|', $this->path, $match);
        if (isset($match[0])) {
            $this->language = trim($match[0], '/');
            $this->path = substr($this->path, strlen($this->language) + 1);
        }
        if (str_starts_with($this->path, '/api/')) {
            $this->isApi = true;
            $this->path = substr($this->path, 4);
        }
        if (str_starts_with($this->path, Router::$projectPrefix)) {
            $this->isProject = true;
            $this->path = substr($this->path, strlen(Router::$projectPrefix)); // 去掉前缀
        } elseif (str_starts_with($this->path, Router::$modulePrefix)) {
            $this->isModule = true;
            $this->path = substr($this->path, strlen(Router::$modulePrefix)); // 去掉前缀
        }

        $this->path = trim($this->path, '/');

        if ($this->isModule) {
            $urlElements = empty($this->path) ? [] : explode('/', $this->path);

            $hasModuleName = isset($urlElements[0]);
            $moduleName = $hasModuleName ? Router::formatName($urlElements[0]) : 'index';

            $first_part = $urlElements[1] ?? 'index';
            $second_part = $urlElements[2] ?? 'index';

            $this->pattern = Router::$modulePrefix . ($hasModuleName ? ':module' : '');
            $this->paths['module'] = $moduleName;
            $this->paths['controller'] = $first_part;
            $this->paths['action'] = $second_part;

            $this->pathsname['module'] = $moduleName;
            $this->pathsname['controller'] = $first_part;
            $this->pathsname['action'] = $second_part;

            $this->namespace = 'App\Modules\\' . $moduleName . '\Controllers';
            $this->viewpath = PATH_APP . 'Modules' . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . 'views';

            $this->name = $moduleName;
            $this->modulePath = PATH_APP . 'Modules' . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . 'Module.php';

            switch (count($urlElements)) {
                case 0:
                    break;
                case 1: // m1 或者 m1.subM
                    $this->paths['module'] = 1;
                    $this->pathsname['controller'] = 'index';
                    $this->subMultipleModulesRoute($moduleName, 'index');
                    break;
                case 2: // m1/c1 或者 m1.subM/c1
                    $this->pattern .= '/:controller';
                    $this->paths['module'] = 1;
                    $this->paths['controller'] = 2;
                    $this->subMultipleModulesRoute($moduleName, $first_part);
                    break;
                case 3: // m1/c1/a1
                    $this->pattern .= '/:controller/:action';
                    $this->paths['module'] = 1;
                    $this->paths['controller'] = 2;
                    $this->paths['action'] = 3;
                    $this->subMultipleModulesRoute($moduleName, $first_part);
                    break;
                default: // a/b/c/d
                    $this->pattern .= '/:controller/:action/:params';
                    $this->paths['module'] = 1;
                    $this->paths['controller'] = 2;
                    $this->paths['action'] = 3;
                    $this->paths['params'] = 4;
                    $this->subMultipleModulesRoute($moduleName, $first_part);
                    break;
            }
        } else { // 普通项目
            $this->pattern = '/';
            $this->paths['controller'] = 'index';
            $this->paths['action'] = 'index';
            $this->namespace = 'App\\Http\\Controllers';
            $this->viewpath = PATH_APP . 'Http' . DIRECTORY_SEPARATOR . 'views';

            // 如果是 Project 项目
            if ($this->isProject) {
                $urlElements = explode('/', $this->path);
                $hasProjectName = isset($urlElements[0]);
                $projectName = $hasProjectName ? Router::formatName($urlElements[0]) : 'index';
                $this->name = $projectName;
                $this->namespace = 'App\\Projects\\' . $projectName . '\\Controllers';
                $this->viewpath = PATH_APP_PROJECTS . $projectName . DIRECTORY_SEPARATOR . 'views';
                $this->path = substr($this->path, strlen($projectName) + 1);
            } elseif ($this->loadDefault) {
                $this->loadDefaultProject();
            }

            $urlElements = empty($this->path) ? [] : explode('/', $this->path);

            $first_part = $urlElements[0] ?? 'index';
            $second_part = $urlElements[1] ?? 'index';
            $this->pathsname['controller'] = $first_part;
            $this->pathsname['action'] = $second_part;

            switch (count($urlElements)) {
                case 0:
                    break;
                case 1:
                    $this->pattern = '/:controller';
                    $this->paths['controller'] = 1;
                    $this->subAppControllerRoute($first_part);
                    break;
                case 2:
                    // 可能是 controller/action 或者 sub.controller/action
                    $this->pattern = '/:controller/:action';
                    $this->paths['controller'] = 1;
                    $this->paths['action'] = 2;
                    $this->subAppControllerRoute($first_part);
                    break;
                default:
                    /*
                     * url 可能是
                     * controller/action/params
                     * sub.controller/action/params
                     * m/sub.controller/action
                     * m/sub.controller/action/params
                     */
                    $this->pattern = '/:controller/:action/:params';
                    $this->paths = ['controller' => 1, 'action' => 2, 'params' => 3];
                    // sub.controller/action/params
                    if (str_contains($first_part, '.')) {
                        $this->subAppControllerRoute($first_part);
                    } elseif (str_contains($second_part, '.')) { // 子模块
                        $this->namespace = str_replace(
                            '\\Controllers',
                            '\\A0\\' . $first_part . '\\Controllers',
                            $this->namespace
                        );
                        $this->viewpath = str_replace(
                            DIRECTORY_SEPARATOR . 'views',
                            DIRECTORY_SEPARATOR . 'A0' . DIRECTORY_SEPARATOR . $first_part . DIRECTORY_SEPARATOR . 'views',
                            $this->viewpath
                        );
                        $this->subm = $first_part;
                        $this->pathsname['action'] = $urlElements[2] ?? 'index';
                        self::subAppControllerRoute($second_part);
                        if (count($urlElements) == 3) {
                            $this->pattern = '/:controller/:action';
                            unset($this->paths['params']);
                        }
                    }
                    break;
            }
        }
        // /cn/m/xxx, /cn/api/m/xxx, /cn/p/xxx, /cn/api/p/xxx
        if ($this->isProject) {
            $this->pattern = '/p/' . $this->name . $this->pattern;
        }
        if ($this->isApi) {
            $this->pattern = '/api' . $this->pattern;
        }

        if ($this->language) {
            $this->pattern = Router::$languageRule . $this->pattern;
            foreach ($this->paths as $key => $value) {
                if (is_integer($value)) {
                    $this->paths[$key] = $value + 1;
                }
            }
        }

        if (!empty($this->subc)) {
            $this->pickview = $this->subc . '/' . Router::formatPickView(
                    $this->pathsname['controller'],
                    $this->pathsname['action']);
        }

        // 多模块时注册模块
        if ($this->isModule) {
            $hasModulePath = file_exists($this->modulePath);
            $this->registerModules = [
                $this->name => [
                    'path' => $hasModulePath
                        ? $this->modulePath
                        : dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Mvc' . DIRECTORY_SEPARATOR . 'Module.php',
                    'className' => $hasModulePath
                        ? 'App\Modules\\' . $this->name . '\Module'
                        : 'Phax\Mvc\Module',
                ]
            ];
        }

        $this->route = $this->pattern;
        if ($this->subm) {
            if ($this->isModule) {
                $this->route = str_replace(':module', ':module\.' . $this->subm, $this->route);
            } else {
                $this->route = str_replace(':controller', $this->subm . '/:controller', $this->route);
            }
        }

        if ($this->subc) {
            $this->route = str_replace(
                ':controller',
                $this->subc . '\.([a-zA-Z0-9\_\-]+)', $this->route
            );
        }

        $requestURI = Router::getURLPath($this->requestURI);
        if (str_ends_with($requestURI, '/')) {
            if (!str_ends_with($this->route, '/')) {
                $this->route .= '/';
            }
        } else {
            $this->route = rtrim($this->route, '/');
        }
        $this->pathsname['controller'] = Router::formatName($this->pathsname['controller']);
        $this->pathsname['action'] = Router::formatName($this->pathsname['action']);
    }

    /**
     * 多模块的子模块/子目录功能
     * @return void
     */
    public function subMultipleModulesRoute(string $module, string $controller): void
    {
        $subModule = str_contains($module, '.'); // 子模块
        $subControl = str_contains($controller, '.'); // 子目录
        if ($subModule) {
            $m = explode('.', $module);
            if (count($m) != 2) {
                throw new \Exception('多模块子模块示例: /m/m1.m2/controller/action/params');
            }
            $this->pathsname['module'] = $m[0];
            $this->namespace = str_replace('.', '\A0\\', $this->namespace);
            $this->viewpath = str_replace('.', DIRECTORY_SEPARATOR . 'A0' . DIRECTORY_SEPARATOR, $this->viewpath);
            $this->modulePath = str_replace($module, $m[0], $this->modulePath);
            $this->name = $m[0];
            $this->subm = $m[1];
            if (!$subControl) {
                return;
            }
        }
        // 子目录
        if ($subControl) {
            $g = explode('.', $controller);
            if (count($g) != 2) {
                throw new \Exception('多模块子目录: /m/m1/ext.controller/action/params');
            }
            $this->pathsname['controller'] = $g[1];
            $this->namespace .= ('\\' . $g[0]);
            $this->subc = $g[0];
        }
    }

    /**
     * 单应用默认首页/子目录
     */
    public function subAppControllerRoute(string $controller): void
    {

        $cc = explode('.', $controller);
        $ccLen = count($cc);
        if ($ccLen == 2) {
            $this->pathsname['controller'] = $cc[1];
            $this->namespace .= ('\\' . $cc[0]);
            $this->subc = $cc[0];
        } elseif ($ccLen > 2) {
            throw new \Exception('子目录示例: /c1/sub.a1/params');
        }
    }

    public function data(): array
    {
        $view = AppService::view();
        return [
            'TODO 路由上下文信息',
            '布局文件' => $view->getMainView(),
            '视图目录' => $view->getViewsDir(),
            '模板名称' => $this->pickview,
            '模板路径' => $this->getPathOfRenderViewTemplate() . Route::TEMPLATE_SUFFIX
        ];
    }

    public function getProject(string $default = ''): string
    {
        if ($this->isProject) {
            return $this->name;
        }
        return AppService::config()->getString('app.project') ?: $default;
    }

    public function getControllerClass(): string
    {
        return $this->namespace . '\\' . ucfirst(
                $this->pathsname['controller']
            ) . 'Controller';
    }

    public function getControllerName(): string
    {
        return ucfirst(
                $this->pathsname['controller']
            ) . 'Controller';
    }

    /**
     * 操作方法 indexAction
     * @return string
     */
    public function getAction(): string
    {
        return $this->pathsname['action'] . 'Action';
    }

    /**
     * 当前操作器名称
     * @return string 示例 `index`
     */
    public function getActionName(): string
    {
        if (!empty($this->paths['action'])) {
            return $this->paths['action'];
        }
        return 'index';
    }

    public function isApiRequest(): bool
    {
        if ($this->isApi) {
            return true;
        } elseif (IS_PHP_FPM) {
            /**
             * @var $request \Phalcon\Http\RequestInterface
             */
            $request = AppService::request();
            return $request->isAjax() || str_contains($request->getServer('HTTP_ACCEPT') ?: '', 'application/json');
        }
        return false;
    }

    /**
     * 获取默认视图文件所在目录（包含主题）
     * @return string
     */
    public function getViewDIR(): string
    {
        return $this->viewpath . ($this->theme ? DIRECTORY_SEPARATOR . $this->theme : '');
    }

    /**
     * 合成视图文件所在目录（包含主题）
     * @param string $pathname 待合成的目录名称或者路径
     * @return string 返回 pathname/views/ 或者 pathname/views/theme/
     */
    public function getViewDIRFor(string $pathname): string
    {
        return $pathname . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . ($this->theme ? $this->theme . DIRECTORY_SEPARATOR : '');
    }

    /**
     * 获取渲染模板绝对路径(不含扩展名 .phtml)（不检查模板是否存在）
     * @return string
     */
    public function getPathOfRenderViewTemplate(): string
    {
        return $this->getViewDIR() . DIRECTORY_SEPARATOR . $this->getPickView();
    }

    /**
     * 返回当前渲染的模板的名称
     * @return string open.form/rent
     */
    public function getPickView(): string
    {

        if (empty($this->pickview)) {
            $this->pickview = Router::formatPickView(
                $this->pathsname['controller'],
                $this->pathsname['action']
            );
            if ($this->subc) { // 子目录
                $this->pickview = $this->subc . DIRECTORY_SEPARATOR . $this->pickview;
            }
        }
        return $this->pickview;
    }

    /**
     * 渲染指定的模板（必须存在 self::$options['viewpath'] 目录下），不会修改当前路由的 action 名称
     * @param string $pickViewName 模板名称 index/index_mobile
     * @return void
     */
    public function setPickView(string $pickViewName): void
    {
        $this->pickview = $pickViewName;
        AppService::view()->pick($pickViewName);
    }
}