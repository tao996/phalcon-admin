<?php

namespace App\Modules\tao\Controllers;

use App\Modules\tao\BaseController;
use App\Modules\tao\sdk\SdkHelper;
use Hybridauth\Hybridauth;
use Phax\Support\Exception\BlankException;
use Phax\Support\Logger;

class Oauth3Controller extends BaseController
{
    protected array|string $openActions = '*';
    public bool $disableUpdateActions = true;

    protected function afterInitialize(): void
    {
        SdkHelper::hybridauth();
    }

// https://hybridauth.github.io/introduction.html
    public function indexAction()
    {
        if (!$this->request->getQuery('d')) {
            return $this->error('请求参数错误 d=driver');
        }
        if ($this->isLogin()) {
            $this->vv->redirectHelper()->read(true);
            return $this->error('请先退出登录');
        }
        if (!$this->vv->request()->hasQuery('state')) {
            if ($redirect = $this->request->getQuery('_redirect')) {
                $this->vv->redirectHelper()->save($redirect);
            }
        }
        $driver = strtolower($this->request->getQuery('d'));
        $config = [
            'callback' => $this->vv->urlModule('tao/oauth3', ['d' => $driver]),
            'providers' => [
                'Google' => $this->vv->registerHelper()->googleProvider(),
            ]
        ];
        $provider = ucwords($driver);
//        dd($config,$provider);
        if (empty($config['providers'][$provider])) {
            return $this->error('匹配不到 Provider');
        }
        if (!$config['providers'][$provider]['enabled']) {
            return $this->error('未启用的授权 Provider');
        }

        try {
            $hy = new Hybridauth($config);
            $adapter = $hy->authenticate($provider);


            $userProfile = $adapter->getUserProfile();
            $adapter->disconnect();
        } catch (\Exception $e) {
            return $this->error(Logger::message($driver . ' 授权错误，请查看日志', $e->getMessage(), false));
        }

        $user = $this->vv->userService()->addUserProfile($userProfile);
        $this->loginAuth->saveUser($user->toArray());
        $this->vv->redirectHelper()->read();

        throw new BlankException('登录成功：跳转到登录页'); // 不会执行到这里
    }
}