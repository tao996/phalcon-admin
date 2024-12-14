<?php

namespace Phax\Foundation;

use Phalcon\Di\Di;
use Phax\Helper\MyBaseUri;
use Phax\Support\Config;
use Phax\Support\Router;

class Route
{
    /**
     * @var array{pattern:string,paths:array{module:string,controller:string,action:string},pathsname:array{module:string,controller:string,action:string},namespace:string,viewpath:string,project:string,route:string,pickview:string}
     */
    public array $routerOptions = [];
    /**
     * 获取当前 URL 中的组成
     * @var array{sw:bool,language:string,api:bool,project:bool,module:bool,path:string}
     */
    public array $urlOptions = [];
    /**
     * 主题
     * @var string
     */
    public string $theme = '';
    public \Phalcon\Mvc\View $view;
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
     * @param string $requestURI
     */
    public function __construct(public string $requestURI, public Di $di, bool $lazyLoadURI = false)
    {
        if (!$lazyLoadURI) {
            $this->loadRequestURI();
        }
    }

    public function loadRequestURI():void
    {
        $this->urlOptions = Router::pathMatch($this->requestURI);
    }

    /**
     * 当前访问的域名
     * @return string
     */
    public function origin(): string
    {
        if (empty($this->origin)) {
            $baseUri = new MyBaseUri($this->di);
            $this->origin = $baseUri->getOrigin();
            Config::$local_assets_origin = rtrim($this->origin, '/');
        }
        return $this->origin;
    }

    public function view(): \Phalcon\Mvc\View
    {
        if (empty($this->view)) {
            $this->view = $this->di->get('view');
        }
        return $this->view;
    }

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
     * 视图文件所在目录
     * @return string
     */
    public function getViewPath(): string
    {
        return $this->routerOptions['viewpath'] . ($this->theme ? '/' . $this->theme : '');
    }

    private string $pickview = '';

    /**
     * 返回当前渲染的模板的名称
     * @return string open.form/rent
     */
    public function getPickView(): string
    {
        if (empty($this->pickview)) {
            $pickview = Router::formatPickView(
                $this->routerOptions['pathsname']['controller'],
                $this->routerOptions['pathsname']['action']
            );
            if (!empty($this->routerOptions['subc'])) { // 子目录
                $this->routerOptions['pickview'] = $this->routerOptions['subc'] . '/' . $pickview;
            } else {
                $this->routerOptions['pickview'] = $pickview;
            }
            $this->pickview = $this->routerOptions['pickview'];
        }
        return $this->pickview;
    }

    /**
     * 设置视图相关数据
     * @return void
     */
    public function doneView(): void
    {
        $viewPath = $this->getViewPath();
        $this->view()->setViewsDir($viewPath);

        // layout view 未指定
        foreach (['.phtml', '.volt'] as $ext) {
            $mf = $viewPath . '/index'; // 布局文件
            if (file_exists($mf . $ext)) {
                $this->routerOptions['mainView'] = $mf;
                break;
            }
        }

        if (empty($this->routerOptions['mainView'])) {
            if (isset($this->routerOptions['module'])) {
                $this->routerOptions['mainView'] = '/var/www/App/Modules/' . $this->mergeFileViewWithTheme(
                        $this->routerOptions['name']
                    ) . 'index';
            } elseif (!empty($this->routerOptions['project'])) {
                $this->routerOptions['mainView'] = '/var/www/App/Projects/' . $this->mergeFileViewWithTheme(
                        $this->routerOptions['project']
                    ) . 'index';
            } elseif ($index = strpos($this->routerOptions['viewpath'], '/A0/')) {
                $this->routerOptions['mainView'] = $this->mergeFileViewWithTheme(
                        substr($this->routerOptions['viewpath'], 0, $index)
                    ) . 'index';
            }
        }
        if (!empty($this->routerOptions['mainView'])) {
            $this->view()->setMainView($this->routerOptions['mainView']);
        }

        $pickView = $this->getPickView();
//        ddd($this->routerOptions, $this->view()->getViewsDir(), $this->view()->getMainView());
        $this->view()->pick($pickView); // 你可以在控制器中随机修改
    }

    /**
     * 指向模板目录
     * @param string $pathname 待合成的目录名称或者路径
     * @return string 返回 pathname/views/ 或者 pathname/views/theme/
     */
    public function mergeFileViewWithTheme(string $pathname): string
    {
        return $pathname . '/views/' . ($this->theme ? $this->theme . '/' : '');
    }

    public function getPathViewTPL(): string
    {
        $f = $this->getViewPath() . '/' . $this->getPickView();
        foreach (['.phtml', '.php', '.volt'] as $suf) {
            if (file_exists($f . $suf)) {
                return $f . $suf;
            }
        }
        return '';
    }

    public function pickView(bool $boolResult = true): string
    {
        $pickview = $this->getViewPath() . '/' . $this->getPickView();
        if (file_exists($pickview . '.phtml')) {
            return $pickview . '.phtml';
        } elseif (file_exists($pickview . '.volt')) {
            return $pickview . '.volt';
        }
        return $boolResult ? false : $pickview;
    }

    /**
     * 渲染指定的模板（必须存在 self::$options['viewpath'] 目录下），不会修改当前路由的 action 名称
     * @param string $pathname 模板名称
     * @return void
     */
    public function changePickView(string $pathname): void
    {
        $this->routerOptions['pickview'] = $pathname;
        $this->view()->pick($this->routerOptions['pickview']);
        $this->pickview = $pathname;
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