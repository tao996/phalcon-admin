<?php

namespace App\Modules\tao;


use App\Modules\tao\Helper\Auth\LoginAuthAdapter;
use App\Modules\tao\Helper\LoginAuthHelper;
use Phax\Mvc\Controller;

class BaseAuthController extends Controller
{
    /**
     * 默认为空，则为 cookies 授权
     * @var LoginAuthAdapter|null|string
     */
    protected LoginAuthAdapter|string|null $loginAdapter = null;
    /**
     * 是否已经检查过登录状态
     * @var bool
     */
    private bool $hasCheckLogin = false;

    protected function isLogin(): bool
    {
        return $this->tryGetLoginAuth()->isLogin();
    }

    /**
     * 尝试获取登录用户的信息
     * @return LoginAuthHelper
     */
    public function tryGetLoginAuth(): LoginAuthHelper
    {
        if (!$this->hasCheckLogin) {
            $this->hasCheckLogin = true;
            TaoAppService::loginAuthHelper()->setAuthAdapter($this->loginAdapter);
            TaoAppService::loginAuthHelper()->login();
        }
        return TaoAppService::loginAuthHelper();
    }
}