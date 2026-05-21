<?php
/*
* Copyright (c) 2024-present
* Author: tao996<lvshutao@outlook.com>
* 
* For the full copyright and license information, please view the LICENSE.txt
* file that was distributed with this source code.
*/

namespace App\Modules\tao\A0\game;

use App\Modules\tao\A0\open\BaseOpenMiniController;
use GatewayClient\Gateway;

require_once PATH_PHAR . 'workerman.phar';
/**
 * 地址来源：App/Workerman/websocket.php
 */
Gateway::$registerAddress = '127.0.0.1:1236';

class GameController extends BaseOpenMiniController
{
    protected array|string $userActions = ['index', 'group', 'test'];

    private function getClientId(): string
    {
        $client_id = $this->requestData['client_id'] ?? '';
        if (empty($client_id)) {
            throw new \Exception('websocket client_id is empty');
        }
        return $client_id;
    }

    private function getGroupId(): string
    {
        $group_id = $this->requestData['group_id'] ?? '';
        if (empty($group_id)) {
            throw new \Exception('websocket group_id is empty');
        }
        return $group_id;
    }

    /**
     * 绑定 webSocket 的 client_id
     * https://www.workerman.net/doc/gateway-worker/work-with-other-frameworks.html
     */
    public function indexAction()
    {
        Gateway::bindUid($this->getClientId(), $this->getUserId());
        return true;
    }

    /**
     * 添加用户到指定的群组
     * @return string[]
     * @throws \Exception
     */
    public function groupAction()
    {
        $client_id = $this->getClientId();
        $user_id = $this->getUserId();
        $group_id = $user_id . '_' . time();
        Gateway::joinGroup($client_id, $group_id);
        return [
            'group_id' => $group_id,
        ];
    }

    /**
     * 往群组发送消息
     * @return true
     * @throws \Exception
     */
    public function testAction()
    {
        $client_id = $this->getClientId();
        $group_id = $this->getGroupId();
        Gateway::sendToGroup($group_id, json_encode([
            'form' => $client_id,
            'msg' => 'test',
        ]));
        return true;
    }
}