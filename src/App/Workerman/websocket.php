<?php
/*
* Copyright (c) 2024-present
* Author: tao996<lvshutao@outlook.com>
* 
* For the full copyright and license information, please view the LICENSE.txt
* file that was distributed with this source code.
*/

use Workerman\Worker;
use GatewayWorker\Lib\Gateway;

define('PATH_ROOT', dirname(__DIR__, 2) . '/');
const IS_WORKER_WEB = true;
const PRINT_DEBUG_MESSAGE = true; // 打印调试信息

/**
 * @var $application \Phax\Foundation\Application
 */
$application = require_once PATH_ROOT . 'bootstrap/app.php';
$wp = new \Phax\Bridge\Workerman\WorkermanPhalcon($application, true);
//require_once PATH_ROOT . 'phar-src/workerman/vendor/index.php';
$wp->setWorkerFiles('ws');

/**
 * 如果需要客户端与客户端连接，则使用 GatewayWorker；否则可使用 Workerman
 * https://www.workerman.net/doc/gateway-worker/
 * https://www.workerman.net/doc/gateway-worker/work-with-other-frameworks.html
 */
const IS_BusinessWorker = false;
if (IS_BusinessWorker) {
    $worker = new \GatewayWorker\BusinessWorker();
    $worker->name = 'phalcon';
    $worker->count = 1;
    $worker->registerAddress = '127.0.0.1:1238';
    $worker->eventHandler = Events::class;

    $gateway = new \GatewayWorker\Gateway('websocket://0.0.0.0:80');
    $gateway->name = 'gateway';
    $gateway->count = 2;
    $gateway->lanIp = '127.0.0.1';
    $gateway->startPort = 2900;
    $gateway->registerAddress = '127.0.0.1:1238';
    $gateway->pingInterval = 10; // 心跳检测
    $gateway->pingData = '{"type":"ping"}';

    $register = new \GatewayWorker\Register('text://0.0.0.0:1238');
} else {
    $worker = new Worker('websocket://0.0.0.0:80');
    $worker->onMessage = function (\Workerman\Connection\TcpConnection $connection, $data) {
        // 这个静态变量用来存储电脑浏览器的websocket连接，方便推送使用
        static $daping_connection = null;
        switch ($data) {
            // 发送 daping 字符串的是电脑浏览器，将其连接保存到静态变量中
            case 'daping':
                $daping_connection = $connection;
                break;
            // ping 是心跳数据，用来维持连接，只返回 pong 字符串，无需做其它处理
            case 'ping':
                $connection->send('pong');
                break;
            // 用户手机浏览器发来的祝福语
            default:
                // 直接使用电脑浏览器的连接将祝福语推送给电脑
                if ($daping_connection) {
                    $daping_connection->send($data);
                }
        }
    };
}


/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 * @link [文档](https://www.workerman.net/doc/gateway-worker/event-functions.html)
 */
class Events
{
    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     *
     * @param string $client_id 连接id
     * @throws \Exception
     */
    public static function onConnect(string $client_id): void
    {
        if (PRINT_DEBUG_MESSAGE) {
            echo 'onConnect:', $client_id, PHP_EOL;
        }
        // 向当前client_id发送数据
        Gateway::sendToClient($client_id, "Hello $client_id\r\n");
        // 向所有人发送
        Gateway::sendToAll("$client_id login\r\n");
    }

    /**
     * 当客户端发来消息时触发
     * @param string $client_id 连接id
     * @param mixed $message 具体消息
     * @throws \Exception
     */
    public static function onMessage(string $client_id, mixed $message): void
    {
        if ($message == 'reload') {
            Gateway::sendToClient($client_id, "reload success\r\n");
            return;
        }
        if (json_validate($message)) {
            $message = json_decode($message, true);
        }
        // 向所有人发送
        if (PRINT_DEBUG_MESSAGE) {
            echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id session:" . json_encode(
                    $_SESSION
                ) . " onMessage:" . $message . "\n";
        }
        Gateway::sendToAll("$client_id said $message\r\n");
    }

    /**
     * 当用户断开连接时触发
     * @param string $client_id 连接id
     * @throws \Exception
     */
    public static function onClose(string $client_id): void
    {
        // 向所有人发送
        if (PRINT_DEBUG_MESSAGE) {
            echo 'onClose:', $client_id, PHP_EOL;
        }
        GateWay::sendToAll("$client_id logout\r\n");
    }
}

Worker::runAll();