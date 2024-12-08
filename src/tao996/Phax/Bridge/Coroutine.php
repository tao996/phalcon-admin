<?php

namespace Phax\Bridge;

use Phalcon\Http\RequestInterface;
use Phalcon\Http\ResponseInterface;
use Phalcon\Mvc\Router;
use Phalcon\Mvc\Url;
use Phax\Bridge\Workerman\Cookie;
use Phax\Foundation\Application;
use Phax\Foundation\Route;
use Phax\Support\Exception\BlankException;
use Phax\Support\Exception\LocationException;

/**
 * 需要重写部分
 * Session/Manager 用于了 $_SESSION, $_COOKIE
 * Mvc/Router:request
 * Mvc/Url: $_SERVER 设置 baseUri 可跳过 $_SERVER
 * view: ob_start())
 */
class Coroutine
{

    public HttpDi $httpDi;
    public RequestInterface $request;
    public ResponseInterface $response;

    public function __construct(public Application $application)
    {
        $this->httpDi = new HttpDi();
    }

    public function setRequest(\Phax\Bridge\Workerman\Request $request): static
    {
        $this->request = $request;
        $this->httpDi->setShared('request', $request);
        $request->setHttpDi($this->httpDi);

        return $this;
    }

    public function setResponse(\Phax\Bridge\Workerman\Response $response): static
    {
        $this->response = $response;
        $this->httpDi->setShared('response', $response);
        $response->setHttpDi($this->httpDi);
        return $this;
    }

    public function setCookie(string $cookieClass): static
    {
        $di = $this->httpDi;
        $this->httpDi->setShared(
            'Phalcon\\Http\\Cookie',
            function (
                $name,
                $value = null,
                $expire = 0,
                $path = "/",
                $secure = null,
                $domain = null,
                $httpOnly = null
            ) use ($cookieClass, $di) {
                /**
                 * @var $cookie AbstractCookie
                 */
                $cookie = new $cookieClass($name, $value, $expire, $path, $secure, $domain, $httpOnly);
                $cookie->setHttpDi($di);
                return $cookie;
            }
        );
        return $this;
    }

    public function setCookies(string $responseCookiesClass, array $cookieOptions): static
    {
        $di = $this->httpDi;
        AbstractResponseCookies::$cookieClassName = Cookie::class;
        $this->httpDi->setShared(
            'cookies', function () use ($responseCookiesClass, $cookieOptions, $di) {
            /**
             * @var $cookies AbstractResponseCookies
             */
            if ($cookieOptions['key']) {
                $cookies = new $responseCookiesClass(true, md5($cookieOptions['key']));
            } else {
                $cookies = new $responseCookiesClass();
            }
            $cookies->setHttpDi($di);
            return $cookies;
        }
        );
        return $this;
    }

    /**
     * @throws \Exception
     */
    public function handler(string $uri): ResponseInterface
    {
        $di = Application::di();

        $route = new Route($uri, $this->httpDi);
        $this->httpDi->setShared('route', $route);

        $options = [
            'project' => $di->get('config')->getProject(),
        ];
        $route->routerOptions = \Phax\Support\Router::analysisWithURL($uri, $options);

        $this->httpDi->setShared('url', function () use ($route) {
            $url = new Url();
            $url->setDI($this->httpDi);
            $url->setBaseUri($route->origin());
            return $url;
        });

        $isAjaxRequest = $route->isApiRequest() || $this->request->isAjax();

        $router = new Router(false);
        $route->setRouter($router); // 注入 router
        $this->httpDi->setShared('router', $router);
        $router->setDI($this->httpDi);

        $router->removeExtraSlashes(false); // 必须为 false，因为我们使用了完全匹配
        $router->setDefaultNamespace($route->routerOptions['namespace']);
        // full match
        $router->add($route->routerOptions['route'], $route->routerOptions['paths']);

        $router->handle($uri);

        $ctrl = $route->getControllerClass();

        if (PRINT_DEBUG_MESSAGE) {
            print_r([
                'uri' => $uri,
                'options' => $route->routerOptions,
                'ctrl' => $ctrl,
                'route' => [
                    'controller' => $route->getControllerName(),
                    'action' => $route->getActionName(),
                ],
                'router' => [
                    'namespace' => $router->getNamespaceName(),
                    'controller' => $router->getControllerName(),
                    'action' => $router->getActionName()
                ]
            ]);
        }

        $dispatcher = new \Phalcon\Mvc\Dispatcher();
        $dispatcher->setDI($this->httpDi);
        $dispatcher->setNamespaceName($router->getNamespaceName());
        $dispatcher->setControllerName($router->getControllerName());
        $dispatcher->setActionName($router->getActionName());
        $dispatcher->setParams($router->getParams());
        $this->httpDi->set('dispatcher', $dispatcher);


        try {
            /**
             * @var $obj \Phax\Mvc\Controller
             */
            $obj = new $ctrl();
            $obj->setDI($this->httpDi);
            $obj->route = $route;
            $this->httpDi->set($ctrl, $obj);
            $this->httpDi->copyServices();

            $dispatcher->dispatch();
            $possibleResponse = $dispatcher->getReturnedValue();
            if (!$this->response->isSent()) {
                if ($isAjaxRequest) {
                    return $this->response->setJsonContent($possibleResponse)
                        ->send();
                }
                $view = $this->httpDi->get('view');
                $view->start();
                $view->render($dispatcher->getControllerName(), $dispatcher->getActionName());
                $view->finish();
                $content = $view->getContent();
                return $this->response->setContent($content);
            }
        } catch (LocationException $e) {
            throw $e;
        } catch (BlankException $e) {
            if (!$this->response->isSent()) {
                return $this->response->setContent($this->response->getContent() ?: $e->getMessage());
            }
            return $this->response->setContent($e->getMessage());
        } catch (\Exception $e) {
            if (PRINT_DEBUG_MESSAGE) {
                print_r($e->getTraceAsString());
            }
            $err = $this->application->handleException($e, $this->httpDi);
            return $this->response->setContent($err);
        }
        return $this->response;
    }
}