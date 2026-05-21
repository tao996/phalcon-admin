<?php
/*
* Copyright (c) 2024-present
* Author: tao996<lvshutao@outlook.com>
* 
* For the full copyright and license information, please view the LICENSE.txt
* file that was distributed with this source code.
*/

namespace Phax\Mvc;

use Phax\Helper\MyMvc;

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
class Console extends \Phalcon\Cli\Console
{
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
}