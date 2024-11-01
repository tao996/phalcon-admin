<?php

namespace App\Modules\tao\A0\open\Controllers\weixin;

use App\Modules\tao\A0\open\BaseOpenDeveloperController;
use Phax\Support\Logger;
use Phax\Utils\MyData;

/**
 * 公众号
 * @link [EasyWeChat](https://easywechat.com/6.x/official-account/index.html)
 * @link [微信公众平台测试号](https://mp.weixin.qq.com/debug/cgi-bin/sandbox?t=sandbox/login)
 */
class OfficialController extends BaseOpenDeveloperController
{
    protected array|string $openActions = '*';
    public array $enableActions = ['index'];

    /**
     * 接入示例 https://你的域名/api/m/tao.open/weixin.official?appid=yourwechatappid
     * @throws \Exception
     */
    public function indexAction()
    {
        $query = $this->request->getQuery();
        // 直接跳过不处理
        if (isset($query['skip'])) {
            exit("");
        }
        MyData::mustHasSet($query, ['appid']);
        // 可以添加其它参数，防止接口公开时被攻击
//        if (!isset($query['t']) && $query['t'] != 'xxx') {exit('query lost.');}
        $app = $this->mvc->application()->getOfficial($query['appid']);
        $server = $app->getServer();


        if (!isset($query['echostr'])) {
            $server->with(function (\EasyWeChat\OfficialAccount\Message $message, \Closure $next) use ($app, $server) {
                $data = $message->toArray();
                $openid = $data['FromUserName'];

                if (is_debug()) {
                    Logger::info($data);
                }

                // 事件推送
                // https://developers.weixin.qq.com/doc/offiaccount/Message_Management/Receiving_event_pushes.html
                if ($message->MsgType == "event") {
                    switch ($message->Event) {
                        case "subscribe": // 订阅,需要 “设置与开发 - 基本配置 - IP白名单”
                            $this->mvc->userService()->officialUser($app, $openid);
                            return "感谢关注";
                        case "unsubscribe":
                            $this->mvc->userService()->unsubscribe($data);
                            return "";
                        case "CLICK": // 自定义菜单事件
                            break;
                        case "VIEW": // 点击菜单跳转链接
                            break;
                    }
                    // 普通消息
                    // https://developers.weixin.qq.com/doc/offiaccount/Message_Management/Receiving_standard_messages.html
                } elseif ($message->MsgType == "text") {
                    return 'Todo.' . $data['Content'];
                }
                return $next($message);
            });
        }

        return $server->serve();
    }
}