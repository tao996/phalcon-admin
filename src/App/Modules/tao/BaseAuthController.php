<?php

namespace App\Modules\tao;


use App\Modules\tao\Helper\Auth\LoginAuthAdapter;
use App\Modules\tao\Helper\LoginAuthHelper;
use App\Modules\tao\Helper\MyMvcHelper;
use Phax\Mvc\Controller;

/**
 * @property \App\Modules\tao\Helper\MyMvcHelper $vv
 */
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

    public function initialize(): void
    {
        $this->vv = new MyMvcHelper($this->di);
        parent::initialize();
    }


    /**
     * @throws \Exception
     */
    protected function isLogin(): bool
    {
        return $this->tryGetLoginAuth()->isLogin();
    }


    /**
     * 尝试获取登录用户的信息
     * @return LoginAuthHelper
     * @throws \Exception
     */
    public function tryGetLoginAuth(): LoginAuthHelper
    {
        if (!$this->hasCheckLogin) {
            $this->hasCheckLogin = true;
            $this->vv->loginAuthHelper()
                ->setAuthAdapter($this->loginAdapter);
            $this->vv->loginAuthHelper()->login();
        }
        return $this->vv->loginAuthHelper();
    }
}