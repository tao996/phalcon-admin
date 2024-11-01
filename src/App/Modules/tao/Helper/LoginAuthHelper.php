<?php

namespace App\Modules\tao\Helper;

use App\Modules\tao\Helper\Auth\LoginAppAuthAdapter;
use App\Modules\tao\Helper\Auth\LoginAuthAdapter;
use App\Modules\tao\Helper\Auth\LoginSessionAuthAdapter;
use App\Modules\tao\Helper\Auth\LoginDemoTokenAuthAdapter;
use App\Modules\tao\Models\SystemUser;

class LoginAuthHelper
{
    public function __construct(public MyMvcHelper $mvc)
    {
    }

    public LoginAuthAdapter $authAdapter;

    /**
     * 设置登录验证方式
     * @param LoginAuthAdapter|null|string $authAdapter
     * @throws \Exception
     */
    public function setAuthAdapter(LoginAuthAdapter|null|string $authAdapter = null): void
    {

        if (empty($authAdapter)) {
            if (LoginDemoTokenAuthAdapter::check($this->mvc)) {
                $authAdapter = LoginDemoTokenAuthAdapter::class;
            } elseif (LoginAppAuthAdapter::check($this->mvc)) {
                $authAdapter = LoginAppAuthAdapter::class;
            } else {
                $authAdapter = LoginSessionAuthAdapter::class;
            }
        }
        $this->authAdapter = is_string($authAdapter) ? new $authAdapter($this->mvc) : $authAdapter;
        $this->authAdapter->data();
    }

    /**
     * @throws \Exception
     */
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
            try {
                if ($user = $this->authAdapter->getUser()) {
                    $this->mvc->loginUserHelper()->resetUser($user);
                    $this->user = $user;
                } else {
                    $this->user = new SystemUser();
                }
            } catch (\Exception $e) {
                if (is_debug()) {
                    throw $e;
                }
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
     * @throws \Exception
     */
    public function loginWith(int $userId): void
    {
        if ($userId > 0) {
            if ($user = SystemUser::findFirst($userId)) {
                $this->getAdapter()->saveUser($user->toArray());
                $this->mvc->loginUserHelper()->resetUser($user);
                $this->user = $user;
            } else {
                throw new \Exception('账号不存在');
            }
        }
    }

    /**
     * 退出登录
     * @return void
     * @throws \Exception
     */
    public function logout(): void
    {
        $this->getAdapter()->destroy();
    }
}