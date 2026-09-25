<?php

namespace App\Modules\tao\Helper;

use App\Modules\tao\Config\Config;
use App\Modules\tao\Helper\Auth\LoginAppAuthAdapter;
use App\Modules\tao\Helper\Auth\LoginAppDbAuthAdapter;
use App\Modules\tao\Helper\Auth\LoginAuthAdapter;
use App\Modules\tao\Helper\Auth\LoginSessionAuthAdapter;
use App\Modules\tao\Helper\Auth\LoginDemoTokenAuthAdapter;
use App\Modules\tao\Models\SystemUser;
use App\Modules\tao\Services\UserService;
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
     * 获取 App 登录适配器类名。
     *
     * app.modules.tao.auth_adapter 只接受 db/redis，错误配置直接失败，避免静默使用
     * 与预期不同的凭证存储。
     */
    private static function getAppAuthAdapterClass(): string
    {
        $driver = strtolower(trim(Config::authAdapter()));
        if ($driver === '') {
            $driver = 'redis';
        }

        return match ($driver) {
            'db' => LoginAppDbAuthAdapter::class,
            'redis' => LoginAppAuthAdapter::class,
            default => throw new BusinessException(
                '不支持的 App 登录适配器配置：' . $driver
            ),
        };
    }

    /**
     * 当前请求是否明确属于 App 认证协议。
     *
     * 不使用 Content-Type 单独判断：Web 页面也可能通过 JSON AJAX 保持
     * Session。App 客户端应显式传递 data=jsonbody 或 kind=app；后续请求
     * 还可以通过 Authorization 头识别。
     */
    private static function isAppRequest(): bool
    {
        $request = AppService::request();
        return $request->getQuery('data', 'string') === 'jsonbody'
            || $request->getQuery('kind', 'string') === 'app'
            || $request->hasHeader('Authorization');
    }

    private function clearLoginUser(bool $userLoaded = false): void
    {
        $this->user = null;
        $this->userLoaded = $userLoaded;
        TaoAppService::loginUserHelper()->clearUser();
    }

    /**
     * 设置登录验证方式。
     *
     * 显式传入的适配器优先级最高；自动选择时，测试适配器优先于 App，
     * App 请求再根据配置选择 DB/Redis，最后回退到 Web Session。
     *
     * @param LoginAuthAdapter|string|null $authAdapter
     * @throws \Exception
     */
    public function setAuthAdapter(LoginAuthAdapter|string|null $authAdapter = null): void
    {
        if ($authAdapter instanceof LoginAuthAdapter) {
            $adapter = $authAdapter;
        } elseif (is_string($authAdapter) && $authAdapter !== '') {
            if (!is_a($authAdapter, LoginAuthAdapter::class, true)) {
                throw new BusinessException('登录适配器必须实现 LoginAuthAdapter');
            }
            $adapter = new $authAdapter();
        } elseif ($authAdapter === null || $authAdapter === '') {
            if (LoginDemoTokenAuthAdapter::check()) {
                $adapter = new LoginDemoTokenAuthAdapter();
            } elseif (self::isAppRequest()) {
                $adapterClass = self::getAppAuthAdapterClass();
                $adapter = new $adapterClass();
            } else {
                $adapter = new LoginSessionAuthAdapter();
            }
        } else {
            throw new BusinessException('登录适配器参数无效');
        }

        // 新适配器验证失败时也清理旧状态，避免失败请求继续使用旧用户。
        $this->clearLoginUser();
        unset($this->authAdapter);
        $adapter->data();
        $this->authAdapter = $adapter;
    }

    public function getAdapter(): LoginAuthAdapter
    {
        if (!isset($this->authAdapter)) {
            $this->setAuthAdapter();
        }
        return $this->authAdapter;
    }

    private SystemUser|null $user = null;
    private bool $userLoaded = false;

    /**
     * 登录以获取用户
     * @return void
     * @throws \Exception
     */
    public function login(): void
    {
        // 尝试获取用户信息
        if (!isset($this->authAdapter)) {
            return;
        }
        if ($this->userLoaded) {
            return;
        }
        if ($user = $this->authAdapter->getUser()) {
            UserService::activeStatus($user);
            TaoAppService::loginUserHelper()->resetUser($user);
            $this->user = $user;
        } else {
            $this->clearLoginUser(true);
            return;
        }
        $this->userLoaded = true;
    }

    public function isLogin(): bool
    {
        if (!isset($this->authAdapter)) {
            return false;
        }
        if (!$this->userLoaded) {
            $this->login();
        }
        return $this->user !== null && $this->user->id > 0;
    }


    /**
     * 重新加载用户信息
     * @param int $userId 用户 ID
     * @return void
     */
    public function loginWith(int $userId): void
    {
        if ($userId > 0) {
            $this->clearLoginUser();
            if ($user = SystemUser::findFirst($userId)) {
                UserService::activeStatus($user);
                $this->getAdapter()->saveUser($user);
                TaoAppService::loginUserHelper()->resetUser($user);
                $this->user = $user;
                $this->userLoaded = true;
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
        try {
            $this->getAdapter()->destroy();
        } finally {
            // logout 后本请求视为已加载匿名状态，避免 test/no-op adapter 被 isLogin() 重新加载。
            $this->clearLoginUser(true);
        }
    }
}