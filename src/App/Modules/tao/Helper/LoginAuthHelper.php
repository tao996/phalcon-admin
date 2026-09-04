<?php

namespace App\Modules\tao\Helper;

use App\Modules\tao\Helper\Auth\LoginAppAuthAdapter;
use App\Modules\tao\Helper\Auth\LoginAppDbAuthAdapter;
use App\Modules\tao\Helper\Auth\LoginAuthAdapter;
use App\Modules\tao\Helper\Auth\LoginSessionAuthAdapter;
use App\Modules\tao\Helper\Auth\LoginDemoTokenAuthAdapter;
use App\Modules\tao\Models\SystemUser;
use App\Modules\tao\TaoAppService;
use Phax\Foundation\AppService;
use Phax\Support\Exception\BusinessException;

class LoginAuthHelper
{
    public function __construct()
    {
    }

    public LoginAuthAdapter $authAdapter;

    /**
     * 获取 App 登录适配器类名
     * 通过配置 app.app_auth_adapter 决定使用 Redis 还是 DB 方案
     * 默认为 'redis'，设置为 'db' 时使用数据库持久存储
     */
    private static function getAppAuthAdapterClass(): string
    {
        $driver = AppService::config()->getString('app.app_auth_adapter', 'redis');
        return $driver === 'db'
            ? LoginAppDbAuthAdapter::class
            : LoginAppAuthAdapter::class;
    }

    /**
     * 设置登录验证方式
     * @param LoginAuthAdapter|null|string $authAdapter 如果为 null 则根据环境自动判断；如果为 string 则为类名；
     * @throws \Exception
     */
    public function setAuthAdapter(LoginAuthAdapter|null|string $authAdapter = null): void
    {
        if (empty($authAdapter)) {
            if (AppService::isJsonBodyRequest()) { // 小程序
                $authAdapter = self::getAppAuthAdapterClass();
            } elseif (LoginDemoTokenAuthAdapter::check()) { // for phpunit test
                $authAdapter = LoginDemoTokenAuthAdapter::class;
            } elseif (LoginAppAuthAdapter::check()) {
                if (LoginAppDbAuthAdapter::check()) {
                    $authAdapter = LoginAppDbAuthAdapter::class;
                } else {
                    $authAdapter = self::getAppAuthAdapterClass();
                }
            } else {
                $authAdapter = LoginSessionAuthAdapter::class;
            }
        }
        $this->authAdapter = is_string($authAdapter) ? new $authAdapter() : $authAdapter;
        $this->authAdapter->data();
    }

    public function getAdapter(): LoginAuthAdapter
    {
        if (empty($this->authAdapter)) {
            $this->setAuthAdapter();
        }
        return $this->authAdapter;
    }

    private SystemUser|null $user = null;

    /**
     * 登录以获取用户
     * @return void
     * @throws \Exception
     */
    public function login(): void
    {
        // 尝试获取用户信息
        if (empty($this->authAdapter)) {
            return;
        }
        if (is_null($this->user)) {
            if ($user = $this->authAdapter->getUser()) {
                TaoAppService::loginUserHelper()->resetUser($user);
                $this->user = $user;
            } else {
                $this->user = new SystemUser(); // 一个匿名用户
            }
        }
    }

    public function isLogin(): bool
    {
        if (empty($this->authAdapter)) {
            return false;
        }
        return $this->user && $this->user->id > 0;
    }


    /**
     * 重新加载用户信息
     * @param int $userId 用户 ID
     * @return void
     */
    public function loginWith(int $userId): void
    {
        if ($userId > 0) {
            if ($user = SystemUser::findFirst($userId)) {
                $this->getAdapter()->saveUser($user);
                TaoAppService::loginUserHelper()->resetUser($user);
                $this->user = $user;
            } else {
                throw new BusinessException('账号不存在');
            }
        }
    }

    /**
     * 退出登录
     * @return void
     */
    public function logout(): void
    {
        $this->getAdapter()->destroy();
    }
}