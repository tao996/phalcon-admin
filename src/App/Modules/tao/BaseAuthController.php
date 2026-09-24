<?php

namespace App\Modules\tao;


use App\Modules\tao\Helper\Auth\LoginAuthAdapter;
use App\Modules\tao\Helper\LoginAuthHelper;
use Phax\Mvc\Controller;

class BaseAuthController extends Controller
{
    /**
     * 默认为空，则按请求自动选择；普通 Web 请求回退到 Session
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
            try {
                TaoAppService::loginAuthHelper()->setAuthAdapter($this->loginAdapter);
                TaoAppService::loginAuthHelper()->login();
            } catch (\Throwable $e) {
                $this->hasCheckLogin = false;
                throw $e;
            }
        }
        return TaoAppService::loginAuthHelper();
    }
}