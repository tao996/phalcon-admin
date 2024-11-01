<?php

namespace App\Modules\tao\A0\open\Helper;


use App\Modules\tao\A0\open\Data\Config;
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
            case Config::Tiktok:
                $application = $this->helper->application()->getTiktok($app);
                $response = $application->getClient()
                    ->postJson('api/apps/v2/jscode2session', [
                        'appid' => $app['appid'],
                        'secret' => $app['secret'],
                        'code' => $code
                    ]);
                $data = $this->helper->tiktokHelper()->openAPIResponseResult($response);
                break;
            case Config::Wechat:
                $application = $this->helper->application()->getMini($app);
                $data = $application->getUtils()->codeToSession($code);
                break;
            default:
                throw new \Exception('code2Session app platform is invalid');
        }
        return $data;
    }

}