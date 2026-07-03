<?php

namespace App\Modules\tao\A0\open\Controllers\weixin;

use App\Modules\tao\A0\open\BaseOpenController;
use Phax\Support\Exception\BusinessException;
use Phax\Support\Exception\LocationException;

/**
 * 公众号授权
 * @link https://easywechat.com/6.x/oauth.html
 * @throws \Exception
 */
class AuthController extends BaseOpenController
{
    protected function localInitialize(): void
    {
    }


    public function indexAction()
    {
        if ($this->request->getQuery('user')) { // 只是需要检查用户是否登录
            if ($this->isLogin()) {
                throw new LocationException($this->vv->redirectHelper()->query('/'));
            }
        }


        $appid = $this->request->getQuery('appid');
        if (empty($appid)) {
            throw new BusinessException('appid is empty');
        }
        $scope = $this->request->getQuery('scope', null, 'snsapi_base');
        $target = $this->request->getQuery('target', null, '/');

        if (!$this->helper->wechatHelper()->isMicroMessengerBrowser()) {
            $url = $this->helper->openUrlHelper()->moduleUrl('tao.wechat/auth', ['appid' => $appid]);
            $this->helper->wechatHelper()->renderQrcode($url);
        }

        $app = $this->helper->application()->getOfficial($appid);
        $oauth = $app->getOAuth();


        if (in_array($scope, ['snsapi_base', 'snsapi_userinfo'])) {
            if (!$this->helper->appService()->kindCompare($appid, 'gzh')) {
                throw new BusinessException('appid is not kind of "gzh"');
            }
        } elseif ('snsapi_login' == $scope) {
            if (!$this->helper->appService()->kindCompare($appid, 'web')) {
                throw new BusinessException('appid is not kind of "web"');
            }
        }
        $oauth->scopes([$scope]);

        $absURL = $this->helper->openUrlHelper()->moduleUrl('tao.wechat/auth/code', [
            'appid' => $appid,
            'target' => $target, // 授权后跳转到此地址
        ]);
        $redirectURL = $oauth->redirect($absURL);
        throw new LocationException($redirectURL);
    }

    public function codeAction()
    {
        $data = $this->request->getQuery();
        $this->vv->validate()->check($data, ['appid' => 'required', 'code' => 'required']);
        $appid = $data['appid'];
        $code = $data['code'];
        $app = $this->helper->application()->getOfficial($appid);
        $oauth = $app->getOAuth();

        $user = $oauth->userFromCode($code);
        $info = $user->toArray();
//        dd('info', $info, $_SERVER);
        $redirect = $this->request->getQuery('target', null, '/');
        $redirectURL = $this->helper->openUrlHelper()->moduleUrl(
            $redirect,
            ['openid' => $info['id'], 'appid' => $appid],
        ); // 跳转到回调地址
        throw new LocationException($redirectURL);
    }
}