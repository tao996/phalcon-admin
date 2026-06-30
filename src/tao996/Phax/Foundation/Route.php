<?php

namespace Phax\Foundation;

use Phalcon\Di\Di;
use Phax\Helper\MyBaseUri;
use Phax\Support\Config;
use Phax\Support\Exception\BusinessException;
use Phax\Support\Router;

require_once PATH_ROOT . 'routes/web.php';

class Route
{
    /**
     * 路由映射，示例  ['/login'=>'/m/tao/auth/index']，当访问 /login 时，实际路由映射为 /m/tao/auth/index
     * @var array
     */
    public static array $mapRoute = [];


    /**
     * @var array{pattern:string,paths:array{module:string,controller:string,action:string},pathsname:array{module:string,controller:string,action:string},namespace:string,viewpath:string,project:string,route:string,pickview:string}
     */
    public array $routerOptions = [];
    /**
     * 获取当前 URL 中的组成
     * @var array{language:string,api:bool,project:bool,module:bool,path:string,mapurl:string}
     */
    public array $urlOptions = [];
    /**
     * 主题
     * @var string
     */
    public string $theme = '';
    /**
     * 当前访问的域名
     * @var string
     */
    private string $origin = '';
    /**
     * 当前的项目
     * @var string
     */
    private string $project = '';
    /**
     * 模板扩展名后缀
     */
    public const string TEMPLATE_SUFFIX = '.phtml';

    /**
     * @param string $requestURI
     */
    public function __construct(public string $requestURI, public Di $di)
    {
        $index = strpos($requestURI, '?');
        $path = $index === false ? $requestURI : substr($requestURI, 0, $index);
        $this->requestURI = self::$mapRoute[$path] ?? $path;
        $this->urlOptions = Router::pathMatch($this->requestURI);
        $map_path = '/' . $this->urlOptions['path'];
        if (isset(self::$mapRoute[$map_path])) {
            $this->urlOptions = Router::pathMatch(self::$mapRoute[$map_path]);
        }
        $this->appOrigin();
    }

    /**
     * 当前访问的域名
     * @return string http://localhost:8071/
     */
    public function appOrigin(): string
    {
        if (empty($this->origin)) {
            $baseUri = new MyBaseUri($this->di);
            $this->origin = $baseUri->getOrigin();
        }
        return $this->origin;
    }

    public function view(): \Phalcon\Mvc\View
    {
        return $this->di->get('view');
    }

    /**
     * 当前操作器名称
     * @return string 示例 `index`
     */
    public function getAction(): string
    {
        if (!empty($this->routerOptions['paths']['action'])) {
            return $this->routerOptions['paths']['action'];
        }
        return 'index';
    }


    /**
     * 获取当前访问节点命名（通常用于做权限管理）
     * @param array $options 配置信息
     * @return string
     */
    public function getNode(array $options = []): string
    {
        if (empty($options)) {
            $options = $this->routerOptions;
        }
        $isSubM = isset($options['subm']); // 子模块
        $isSubC = isset($options['subc']); // 子目录
        if (isset($options['module'])) {
            if (!$isSubM && !$isSubC) {
                return join('/', $options['pathsname']);
            }
            if ($isSubM && !$isSubC) {
                return join('/', [
                    $options['pathsname']['module'] . '.' . $options['subm'],
                    $options['pathsname']['controller'],
                    $options['pathsname']['action']
                ]);
            }
            if (!$isSubM && $isSubC) {
                return join('/', [
                    $options['pathsname']['module'],
                    $options['subc'] . '.' . $options['pathsname']['controller'],
                    $options['pathsname']['action']
                ]);
            }
            if ($isSubM && $isSubC) {
                return join('/', [
                    $options['pathsname']['module'] . '.' . $options['subm'],
                    $options['subc'] . '.' . $options['pathsname']['controller'],
                    $options['pathsname']['action']
                ]);
            }
        } else {
            if (!$isSubM && !$isSubC) {
                return join('/', $options['pathsname']);
            }
            if (!$isSubM && $isSubC) {
                return join('/', [
                    $options['subc'] . '.' . $options['pathsname']['controller'],
                    $options['pathsname']['action'],
                ]);
            }
            if ($isSubM && $isSubC) {
                return join('/', [
                    $options['subm'],
                    $options['subc'] . '.' . $options['pathsname']['controller'],
                    $options['pathsname']['action'],
                ]);
            }
        }
        return '';
    }


    /**
     * 获取默认视图文件所在目录（包含主题）
     * @return string
     */
    public function getViewDIR(): string
    {
        return $this->routerOptions['viewpath'] . ($this->theme ? DIRECTORY_SEPARATOR . $this->theme : '');
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
     * @var string 当前渲染用的模板，如 index/index
     */
    private string $pickView = '';

    /**
     * 返回当前渲染的模板的名称
     * @return string open.form/rent
     */
    public function getPickView(): string
    {
        if (empty($this->pickView)) {
            $pickview = Router::formatPickView(
                $this->routerOptions['pathsname']['controller'],
                $this->routerOptions['pathsname']['action']
            );
            if (!empty($this->routerOptions['subc'])) { // 子目录
                $this->routerOptions['pickview'] = $this->routerOptions['subc'] . DIRECTORY_SEPARATOR . $pickview;
            } else {
                $this->routerOptions['pickview'] = $pickview;
            }
            $this->pickView = $this->routerOptions['pickview'];
        }
        return $this->pickView;
    }


    /**
     * 渲染指定的模板（必须存在 self::$options['viewpath'] 目录下），不会修改当前路由的 action 名称
     * @param string $pickViewName 模板名称 index/index_mobile
     * @return void
     */
    public function setPickView(string $pickViewName): void
    {
        $this->routerOptions['pickview'] = $pickViewName;
        $this->view()->pick($this->routerOptions['pickview']);
        $this->pickView = $pickViewName;
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
     * 设置视图相关数据
     * @return void
     */
    public function doneView(): void
    {
        $viewPath = $this->getViewDIR();
        $this->view()->setViewsDir($viewPath); // 设置视图目录

        // 布局文件
        $layoutViewPath = $viewPath . DIRECTORY_SEPARATOR . 'index';
        if (file_exists($layoutViewPath . self::TEMPLATE_SUFFIX)) {
            $this->routerOptions['mainView'] = $layoutViewPath;
        } elseif (empty($this->routerOptions['mainView'])) {
            // 模块布局文件
            if (isset($this->routerOptions['module'])) {
                $this->routerOptions['mainView'] = PATH_APP_MODULES . $this->getViewDIRFor(
                        $this->routerOptions['name']
                    ) . 'index';
                // 项目布局文件
            } elseif (!empty($this->routerOptions['project'])) {
                $this->routerOptions['mainView'] = PATH_APP_PROJECTS . $this->getViewDIRFor(
                        $this->routerOptions['project']
                    ) . 'index';
            } elseif ($index = strpos($this->routerOptions['viewpath'], DIRECTORY_SEPARATOR . 'A0' . DIRECTORY_SEPARATOR)) {
                $this->routerOptions['mainView'] = $this->getViewDIRFor(
                        substr($this->routerOptions['viewpath'], 0, $index)
                    ) . 'index';
            }
        }
        // 如果存在布局文件
        if (!empty($this->routerOptions['mainView'])) {
            $this->view()->setMainView($this->routerOptions['mainView']);
        }
        // 检查渲染文件
        $pickView = $this->getPickView();
        $pickViewPath = $this->getPathOfRenderViewTemplate();
        if (file_exists($pickViewPath . self::TEMPLATE_SUFFIX)) {
            $this->view()->pick($pickView); // 你可以在控制器中随机修改
        } else {
            if (IS_DEBUG) {
                ddd(['routerOptions' => $this->routerOptions,
                    '视图目录' => $this->view()->getViewsDir(),
                    '布局文件' => $this->view()->getMainView(),
                    '模板名称' => $pickView,
                    '模板路径' => $pickViewPath . self::TEMPLATE_SUFFIX
                ]);
            } else {
                throw new BusinessException('待渲染的模板不存在');
            }
        }
    }


    public function getLanguage(): string
    {
        return $this->urlOptions['language'] ?: '';
    }

    public function isApiRequest(): bool
    {
        if ($this->urlOptions['api']) {
            return true;
        } elseif (IS_PHP_FPM) {
            /**
             * @var $request \Phalcon\Http\RequestInterface
             */
            $request = $this->di->get('request');
            return $request->isAjax() || str_contains($request->getServer('HTTP_ACCEPT') ?: '', 'application/json');
        }
        return false;
    }

    public function isMultipleModules(): bool
    {
        return isset($this->routerOptions['module']);
    }

    public function getProject(string $default = '')
    {
        if (!empty($this->routerOptions['project'])) {
            return $this->routerOptions['project'];
        }
        /**
         * @var Config $config
         */
        $config = $this->di->get('config');
        return $config->getProject() ?: $default;
    }

    public function getControllerClass(): string
    {
        return $this->routerOptions['namespace'] . '\\' . ucfirst(
                $this->routerOptions['pathsname']['controller']
            ) . 'Controller';
    }

    public function getControllerName(): string
    {
        return ucfirst(
                $this->routerOptions['pathsname']['controller']
            ) . 'Controller';
    }

    public function getActionName(): string
    {
        return $this->routerOptions['pathsname']['action'] . 'Action';
    }

    public function setRouter(\Phalcon\Mvc\Router $router)
    {
        $router->setDefaultNamespace($this->routerOptions['namespace']);
        // 添加到路由
        $router->add($this->routerOptions['route'], $this->routerOptions['paths']);
        $router->setDefaultController($this->getControllerName());
        $router->setDefaultAction($this->getActionName());
    }
}