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


require_once PATH_ROOT . 'tao996/index.php';
$wp = new \Phax\Bridge\Workerman\WorkermanPhalcon(null, true);
//require_once PATH_ROOT . 'phar-src/workerman/vendor/index.php';
$wp->setWorkerFiles('ws');

/*
 * 如果需要客户端与客户端连接，则使用 GatewayWorker；否则可使用 Workerman
 * https://www.workerman.net/doc/gateway-worker/
 */

/**
 * https://www.workerman.net/doc/gateway-worker/business-worker.html
 * BusinessWorker 运行业务逻辑的进程
 */
$worker = new \GatewayWorker\BusinessWorker();
$worker->name = 'phalcon';
$worker->count = 1;
$worker->registerAddress = ['127.0.0.1:1236'];
$worker->eventHandler = Events::class; // 设置业务处理类

/**
 * https://www.workerman.net/doc/gateway-worker/gateway.html
 * Gateway进程是暴露给客户端的让其连接的进程。
 * 所有客户端的请求都是由Gateway接收然后分发给BusinessWorker处理，
 * 同样BusinessWorker也会将要发给客户端的响应通过Gateway转发出去。
 */
$gateway = new \GatewayWorker\Gateway('websocket://0.0.0.0:80');
$gateway->name = 'gateway';
$gateway->count = 2;
$gateway->lanIp = '127.0.0.1';
$gateway->startPort = 2900;
$gateway->registerAddress = ['127.0.0.1:1236'];;
$gateway->pingInterval = 10; // 心跳检测
$gateway->pingData = '{"type":"ping"}';

/**
 * https://www.workerman.net/doc/gateway-worker/register.html
 * Gateway进程和BusinessWorker进程启动后分别向Register进程注册自己的通讯地址，
 * Gateway进程和BusinessWorker通过Register进程得到通讯地址后，就可以建立起连接并通讯了
 */
new \GatewayWorker\Register('text://0.0.0.0:1236');


/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 * @link [文档](https://www.workerman.net/doc/gateway-worker/event-functions.html)
 * https://www.workerman.net/doc/gateway-worker/work-with-other-frameworks.html
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

        Gateway::sendToClient($client_id, json_encode([
            'type' => 'init',
            'client_id' => $client_id,
        ]));
    }

    /**
     * 当客户端发来消息时触发
     * @param string $client_id 连接id
     * @param mixed $message 具体消息
     * @throws \Exception
     */
    public static function onMessage(string $client_id, mixed $message): void
    {
        if (PRINT_DEBUG_MESSAGE) {
            echo 'onMessage:', $client_id, PHP_EOL;
            echo json_validate($message) ? json_encode($message) : $message, PHP_EOL;
        }
    }

    /**
     * 当用户断开连接时触发
     * @param string $client_id 连接id
     * @throws \Exception
     */
    public static function onClose(string $client_id): void
    {
        if (PRINT_DEBUG_MESSAGE) {
            echo 'onClose:', $client_id, PHP_EOL;
        }
    }
}

Worker::runAll();