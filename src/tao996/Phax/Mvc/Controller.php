<?php

namespace Phax\Mvc;

use Phalcon\Mvc\Dispatcher;
use Phax\Helper\MyMvc;
use Phax\Support\Exception\BlankException;

/**
 * @property \Phax\Foundation\Route $route
 * @property \Phalcon\Db\Profiler $profiler
 * @property \Phalcon\Mvc\Model\MetaData $modelsMetadata
 * @property \Phalcon\Mvc\Model\Manager $modelsManager
 * @property \Phalcon\Mvc\View $view
 * @property \Phalcon\Cache\Cache $cache
 * @property \Phalcon\Session\Manager $session
 * @property \Phalcon\Logger\Logger $logger
 * @property \Redis $redis
 * @property \PDO $pdo
 * @property \Phalcon\Db\Adapter\Pdo\AbstractPdo $db
 * @property \Phalcon\Mvc\Model\Transaction\Manager $transactionManager
 * @property \Phalcon\Html\TagFactory $tag
 * @property \Phalcon\Cli\Router|\Phalcon\Mvc\Router $router
 * @property \Phalcon\Http\Response $response
 * @property \Phalcon\Http\RequestInterface $request
 * @property \Phalcon\Flash\AbstractFlash $flash
 * @property \Phalcon\Events\Manager $eventsManager
 * @property \Phalcon\Html\Escaper $escaper
 * @property \Phalcon\Dispatcher\AbstractDispatcher $dispatcher
 * @property \Phalcon\Http\Response\Cookies $cookies
 * @property \Phalcon\Assets\Manager $assets
 * @property \Phalcon\Annotations\Adapter\Memory $annotations
 * @property \Phax\Helper\MyMvc $vv
 */
class Controller extends \Phalcon\Mvc\Controller
{
    // request/response 等都是在 __get 中获取
//    public function __get(string $propertyName): mixed
//    {
//        parent::__get($propertyName);
//    }

    /**
     * @var bool 是否启用自动响应
     */
    public bool $autoResponse = true;
    /**
     * 是否以 json 方式返回
     * @var bool
     */
    public bool $jsonResponse = false;
    /**
     * @var MyMvc $vv
     */
    public mixed $vv;

    public function initialize(): void
    {
        if (empty($this->vv)) {
            $this->vv = new MyMvc($this->di);
        }
    }

    /**
     * 添加视图数据
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function addViewData(string $name, mixed $value): self
    {
        $this->vv->html()->setVar($name, $value);
        return $this;
    }

    /**
     * 对 api 接口返回数据进行处理
     * @param mixed $data 控制器返回的数据
     */
    protected function beforeJsonResponse(mixed $data)
    {
        return $data;
    }

    /**
     * 对视图数据进行处理
     * @param mixed $data 控制器返回的数据
     */
    protected function beforeViewResponse(mixed $data)
    {
        return $data;
    }

    /**
     * 对响应数据进行处理
     * @param bool $isApi
     * @param mixed $data
     * @throws BlankException
     * @throws \Exception
     */
    protected function doResponse(bool $isApi, mixed $data): void
    {
        if ($isApi) {
            $this->json($data);
        } else {
            $this->vv->html()
                ->setVar('language', $this->vv->getLanguage())
                ->setResponseVar($data)
                ->doneViewResponse();
        }
    }

    /**
     * 处理响应，如果有自己的格式要求，则可以重写这部分的内容
     * @param Dispatcher $dispatcher
     * @return void
     * @throws BlankException
     */
    public function afterExecuteRoute(Dispatcher $dispatcher): void
    {
        if ($this->response->isSent()) {
            return;
        }
        if ($this->autoResponse) {
            $data = $dispatcher->getReturnedValue() ?: []; // 接口返回的数据
            // 获取控制器响应内容，并根据请求样式判断数据响应方式
            if ($this->isApiRequest()) {
                $data = $this->beforeJsonResponse($data);
                $this->doResponse(true, $data);
            } else {
                $data = $this->beforeViewResponse($data);
                $this->doResponse(false, $data);
            }
        }
    }

    public function isApiRequest(): bool
    {
        return $this->jsonResponse || $this->route->isApiRequest();
    }

    // 输出 JSON  内容，通常在控制器中使用
    // 注意：，如果你不是在控制器调用 \json()；那么则需要手动 exit
    // 否则会出现 Phalcon\Http\Response\Exception: Response was already sent
    function json($data): void
    {
        $this->jsonResponse = true;
        $this->response
            ->setContentType('application/json', 'UTF-8')
            ->setContent(json_encode($data))
            ->send();
        throw new BlankException('');
    }
}