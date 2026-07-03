<?php

namespace App\Modules\tao\A0\open\Helper;

use App\Modules\tao\Data\UserBindPlatform;
use Phax\Support\Logger;
use Phax\Utils\MyData;

class MiniAppHelper
{
    public function __construct(private MyOpenMvcHelper $helper)
    {
    }

    /**
     * 获取授权用户的 session_key 和 openid
     * @link https://demo.fushuilu.com/api/m/tao.open/mini-server/code2session
     * @link [抖音小程序]https://developer.open-douyin.com/docs/resource/zh-CN/mini-app/develop/server/log-in/code-2-session
     * @link [微信小程序]https://developers.weixin.qq.com/miniprogram/dev/OpenApiDoc/user-login/code2Session.html
     * @param array $app 应用配置信息
     * @param string $code 小程序 code
     * @return array{openid:string,session_key:string,unionid:string}
     * @throws \Exception
     */
    public function code2Session(array $app, string $code): array
    {
        MyData::mustHasSet($app, ['appid', 'secret', 'kind', 'platform']);

        switch ($app['platform']) {
            case UserBindPlatform::PlatformTiktok:
                $application = $this->helper->application()->getTiktok($app);
                $response = $application->getClient()
                    ->postJson('api/apps/v2/jscode2session', [
                        'appid' => $app['appid'],
                        'secret' => $app['secret'],
                        'code' => $code
                    ]);
                $data = $this->helper->tiktokHelper()->openAPIResponseResult($response);
                break;
            case UserBindPlatform::PlatformWechat:
                $application = $this->helper->application()->getMini($app);
                $data = $application->getUtils()->codeToSession($code);
                break;
            default:
                throw new \Exception('code2Session app platform is invalid');
        }
        return $data;
    }

    /**
     * 发送模板消息
     * @link https://developers.weixin.qq.com/miniprogram/dev/OpenApiDoc/mp-message-management/subscribe-message/sendMessage.html
     * @param array{appid:string, template_id:string, touser:string, data:array, page:string, miniprogram_state:string} $options 配置信息
     */
    public function sendTemplateMessage(string $appid, array $options): \EasyWeChat\Kernel\HttpClient\Response
    {
        $this->helper->mvc->validate()->check($options, [
            'template_id|模板ID' => 'required',
            'touser|接收者（用户）的 openid' => 'required',
            'data|模板内容' => 'required',
            'miniprogram_state|跳转小程序类型' => 'in:developer,trial,formal',
            'lang|语言' => 'required'
        ]);
        $app = $this->helper->application()->getMini($appid);
        // 测试环境为 $app 提供记录引擎
        if (IS_DEBUG) {
            $logger = $this->helper->mvc->logger();
            if ($logger instanceof \Psr\Log\LoggerInterface) {
                $app->setLogger($this->helper->mvc->logger());
            }
        }
        $api = $app->getClient();
        if (IS_DEBUG) {
            Logger::debug('MiniAppHelper.getClient', $appid, $options);
        }
        // "errcode":47001,"errmsg":"data format error —— 因为使用了 post 而不是 postJson
        return $api->postJson('/cgi-bin/message/subscribe/send', $options);
    }

}