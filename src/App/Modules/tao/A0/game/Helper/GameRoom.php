<?php
/*
* Copyright (c) 2024-present
* Author: tao996<lvshutao@outlook.com>
* 
* For the full copyright and license information, please view the LICENSE.txt
* file that was distributed with this source code.
*/

namespace App\Modules\tao\A0\game\Helper;

/**
 * 1. 创建房间
 * 2。 玩家进入房间
 * 3。 玩家坐下准备
 * 4。 检查是否开局，达到可局条件即可开局
 * 5。 广播游戏准备好，马上开始，不允许玩家退出，留时间给客户端读秒准备
 * 6。 开局，广播开始游戏
 * 7。 游戏结束 与 战斗结算
 * 8。 战斗结束（广告结束） ，清理状态
 * 9。 开始下一局
 */
interface GameRoom
{
    /**
     * 创建房间
     * @param int $maxPlayer
     * @return mixed 返回房间 ID
     */
    public function createRoom(int $maxPlayer):mixed;

    /**
     * 玩家进入房间
     * @param string|int $roomId
     * @param string|int $userId
     * @return bool 加入房间成功返回 true
     */
    public function entry(string|int $roomId, string|int $userId):bool;

    /**
     * 玩家准备好
     * @param string|int $roomId
     * @param string|int $userId
     * @return bool
     */
    public function prepare(string|int $roomId, string|int $userId):bool;

    /**
     * 是否可以开局
     * @param string|int $roomId
     * @return bool
     */
    public function canStart(string|int $roomId):bool;
}