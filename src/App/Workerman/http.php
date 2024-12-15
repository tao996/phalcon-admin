<?php

use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Worker;

define('PATH_ROOT', dirname(__DIR__, 2) . '/');
const IS_WORKER_WEB = true;
const PRINT_DEBUG_MESSAGE = false; // 打印调试信息
const PRINT_REQUEST_TIME = true; // 打印响应时间

/**
 * @var $application \Phax\Foundation\Application
 */
$application = require_once PATH_ROOT . 'bootstrap/app.php';
$wp = new \Phax\Bridge\Workerman\WorkermanPhalcon($application, true);
//require_once PATH_ROOT . 'phar-src/workerman/vendor/index.php';


$wp->setWorkerFiles('ws');

$http_worker = new Worker("http://0.0.0.0:80");

// 启动4个进程对外提供服务
$http_worker->count = 1; // 开发环境
$http_worker->onMessage = function (TcpConnection $connection, Request $request) use ($wp) {
    if ('/favicon.ico' == $request->uri()) {
        return $connection->send('');
    }
    if ($wp->staticFile($connection, $request, true)) {
        return true;
    }
    $start_time = 0;
    if (PRINT_REQUEST_TIME) {
        echo $request->uri(), PHP_EOL;
        $start_time = microtime(true);
    }
    $wp->handler($connection, $request);

    if (PRINT_REQUEST_TIME) {
        $end_time = microtime(true);
        echo ' spend ' . ($end_time - $start_time) . 's', PHP_EOL;
    }

    return true;
};
$http_worker->onWorkerStart = function () {
    // show global variables like '%timeout';
    \Workerman\Timer::add(3600, function () {
        /**
         * @var \Phalcon\Db\Adapter\Pdo\AbstractPdo $db
         */
        $db = \Phax\Foundation\Application::di()->get('db');
        $db->query('select 1');
        echo 'ping db:' . date('Y-m-d H:i:s'), PHP_EOL;
    });
};

// 运行worker
Worker::runAll();