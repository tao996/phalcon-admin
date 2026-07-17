<?php

namespace Phax\Foundation;

use Phalcon\Di\Di;
use Phax\Support\Exception\BusinessException;
use Phax\Utils\MyData;

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

        $this->appOrigin();
    }

    /**
     * 当前访问的域名
     * @return string http://localhost:8071/
     */
    public function appOrigin(): string
    {
        if (empty($this->origin)) {
            //  app.origin 域名没有配置，示例 https://localhost:8080/
            $this->origin = AppService::config()->getString('app.origin');
            if (!empty($this->origin)) {
                return $this->origin;
            }

            $request = AppService::request();

            $scheme = $request->hasServer('HTTPS')
            && (($request->getServer('HTTPS') == 'on') || ($request->getServer('HTTPS') == 1))
                ? 'https' : 'http';
            $port = '';
            $server_port = $request->getServer('SERVER_PORT') ?: MyData::getInt($_SERVER, 'OPEN_PORT', '80');
            if ($server_port != '80' && $server_port != '443') {
                $port = ':' . $server_port;
            }

            $host = '';
            foreach (
                [
                    $request->getHeader('X-Forwarded-Host'),
                    $request->getServer('HTTP_X_FORWARDED_HOST'),
                    $request->getHeader('HOST'),
                    $request->getServer('HTTP_HOST'),
                    $request->getServer('SERVER_NAME'),
                ] as $v
            ) {
                if ($v) {
                    $host = $v;
                    break;
                }
            }
            if (empty($host)) {
                $host = 'localhost';
            }
            if (str_contains($host, ':')) {
                $host = explode(':', $host)[0];
            }
            $this->origin = "{$scheme}://{$host}{$port}/";
        }
//        ddd($this->origin);
        return $this->origin;
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
     * 设置视图相关数据
     * @return void
     */
    public function doneView(): void
    {
        $context = AppService::routeContext();
        $view = AppService::view();
        $viewDir = $context->getViewDIR();
        $view->setViewsDir($viewDir); // 设置视图目录
        // 布局文件
        $layoutViewPath = $viewDir . DIRECTORY_SEPARATOR . 'index';
        if (file_exists($layoutViewPath . self::TEMPLATE_SUFFIX)) {
            $context->mainView = $layoutViewPath;
        } elseif (empty($context->mainView)) {
            // 模块布局文件
            if (isset($context->isModule)) {
                $context->mainView = PATH_APP_MODULES . $context->getViewDIRFor(
                        $context->name,
                    ) . 'index';
                // 项目布局文件
            } elseif (!empty($context->isProject)) {
                $context->mainView = PATH_APP_PROJECTS . $context->getViewDIRFor(
                        $context->name
                    ) . 'index';
            } elseif ($index = strpos($context->viewpath, DIRECTORY_SEPARATOR . 'A0' . DIRECTORY_SEPARATOR)) {
                $context->mainView = $context->getViewDIRFor(
                        substr($context->viewpath, 0, $index)
                    ) . 'index';
            }
        }
        // 如果存在布局文件
        if (!empty($context->mainView)) {
            $view->setMainView($context->mainView);
        }
        // 检查渲染文件
        $pickViewPath = $context->getPathOfRenderViewTemplate();
        if (file_exists($pickViewPath . self::TEMPLATE_SUFFIX)) {
            $view->pick($context->getPickView()); // 你可以在控制器中随机修改
        } else {
            if (IS_DEBUG) {
                ddd('选择器模板不存在',
                    AppService::routeContext()->data(),
                );
            } else {
                throw new BusinessException('待渲染的模板不存在');
            }
        }
    }
}