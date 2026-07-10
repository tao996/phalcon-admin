<?php

namespace App\Modules\tao\Controllers;

use App\Modules\tao\BaseController;
use App\Modules\tao\sdk\SdkHelper;
use App\Modules\tao\Services\UserService;
use App\Modules\tao\TaoAppService;
use App\Modules\tao\utils\RedirectUtil;
use Hybridauth\Hybridauth;
use Phax\Foundation\AppService;
use Phax\Support\Exception\BlankException;
use Phax\Support\Exception\BusinessException;
use Phax\Support\Exception\LogException;

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
            RedirectUtil::read(true);
            return $this->error('请先退出登录');
        }
        if (!AppService::request()->hasQuery('state')) {
            if ($redirect = $this->request->getQuery('_redirect')) {
                // 防止开放重定向攻击：仅允许相对路径或同源绝对路径
                $decoded = urldecode($redirect);
                if (preg_match('/^https?:\/\//i', $decoded) || str_starts_with($decoded, '//')) {
                    $allowedHost = $this->request->getHttpHost();
                    $redirectHost = parse_url($decoded, PHP_URL_HOST);
                    if ($redirectHost !== null && $redirectHost !== $allowedHost) {
                        throw new BusinessException('重定向地址不允许跨域');
                    }
                }
                RedirectUtil::save($redirect);
            }
        }
        $driver = strtolower($this->request->getQuery('d'));
        $config = [
            'callback' => AppService::urlModule('tao/oauth3', ['d' => $driver]),
            'providers' => [
                'Google' => TaoAppService::registerHelper()->googleProvider(),
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
            throw new LogException('授权失败，请稍后再试', [
                'config' => $config,
            ], previous: $e);
        }

        $user = UserService::addUserProfile($userProfile);
        $this->getLoginAdapter()->saveUser($user);
        RedirectUtil::read();

        throw new BlankException('登录成功：跳转到登录页'); // 不会执行到这里
    }
}