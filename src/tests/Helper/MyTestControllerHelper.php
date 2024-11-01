<?php

namespace Tests\Helper;


use Phax\Foundation\Application;
use Phax\Foundation\Route;
use Phax\Mvc\Controller;
use Phax\Support\Router;
use Tests\Helper\services\Request;
use Tests\Helper\services\Response;
use Tests\Helper\services\Session;

class MyTestControllerHelper
{
    public \Phalcon\Http\RequestInterface $request;
    public \Phalcon\Http\Response $response;
    public \Phalcon\Session\Manager $session;
    public Route $route;

    public Controller $controller; // 待测试的控制器

    /**
     * controller action test
     * <code>
     * list($tc, $cc) = MyTestControllerHelper::with(UserController::class);
     * $tc->initialize();
     * $ud = $cc->initAction();
     *
     * $this->assertEquals(1000, $ud['user_id']);
     * $this->assertNotEmpty($ud['families']);
     * </code>
     * @param string|Controller $controller
     * @return array{static,Controller} 使用 list($tc, $cc) 进行接收
     * @throws \Exception
     */
    public static function with(string|\Phax\Mvc\Controller $controller): array
    {
        if (is_string($controller)) {
            $controller = new $controller();
        }

        $tc = new static($controller);
        return [$tc, $controller];
    }

    /**
     * @throws \Exception
     */
    public function __construct(\Phax\Mvc\Controller|string $controller = null)
    {
        $di = Application::di();
        $this->request = new Request();
        $this->response = new Response();
        $this->session = new Session();
        $di->set('request', $this->request);
        $di->set('response', $this->response);
        $di->set('session', $this->session);

        if (is_string($controller)) {
            $controller = new $controller();
        }
        $this->route = new Route('/', $di);
        $di->set('route', $this->route);
        $this->replaceRoute('/');

        if ($controller) {
            $this->setController($controller);
        }
    }

    protected function afterSetController(): void
    {
    }


    public function setController(\Phax\Mvc\Controller $controller): static
    {
        $controller->request = $this->request;
        $controller->response = $this->response;
        $controller->session = $this->session;
        $controller->route = $this->route;
        if (property_exists($controller, 'jsonResponse')) {
            $controller->jsonResponse = true;
        }
        $this->controller = $controller;
        $this->afterSetController();
        return $this;
    }

    /**
     * 调用控制器的 initialize 方法
     * @return $this
     */
    public function initialize(): static
    {
        if (method_exists($this->controller, 'initialize')) {
            $this->controller->initialize();
        }
        $this->afterInitialize();
        return $this;
    }

    protected function afterInitialize(): void
    {
    }

    /**
     * 路由服务
     * @param string $requestURL
     * @return $this
     * @throws \Exception
     */
    public function replaceRoute(string $requestURL): static
    {
        $this->route->requestURI = $requestURL;
        $this->route->routerOptions = Router::analysisWithURL($requestURL);
        return $this;
    }


    /**
     * 提交一份 ajax post 数据
     * @param array $data
     * @param bool $isAjax 是否通过 ajax 提交
     * @return MyTestControllerHelper
     */
    public function setPostData(array $data, bool $isAjax = true): static
    {
        $this->request->data['getPost'] = $data;
        if ($isAjax) {
            $this->setAjaxRequest();
        }
        $this->setPostMethod();
        return $this;
    }

    /**
     * 设置 POST 请求
     * @return $this
     */
    public function setPostMethod(): static
    {
        $this->request->data['isPost'] = true;
        $this->request->data['getMethod'] = 'post';
        return $this;
    }

    public function setPutData(array $data, bool $isAjax = true): static
    {
        $this->request->data['getPut'] = $data;
        if ($isAjax) {
            $this->setAjaxRequest();
        }
        $this->setPutMethod();
        return $this;
    }

    /**
     * @return $this
     */
    public function setPutMethod(): static
    {
        $this->request->data['isPut'] = true;
        $this->request->data['getMethod'] = 'put';
        return $this;
    }

    public function setQueryData(array $data, bool $isAjax = true): static
    {
        $this->request->data['getQuery'] = $data;
        if ($isAjax) {
            $this->setAjaxRequest();
        }
        $this->setGetMethod();
        return $this;
    }

    public function setGetMethod(): static
    {
        $this->request->data['isGet'] = true;
        $this->request->data['getMethod'] = 'get';
        return $this;
    }

    public function setAjaxRequest(): static
    {
        $this->request->data['isAjax'] = true;
        return $this;
    }


    /**
     * 返回内容
     * @param mixed $response
     * @return array{code:int,msg:string,data:mixed}
     */
    public function getActionResponse(mixed $response): array
    {
        return $response->getJsonContent();
    }
}