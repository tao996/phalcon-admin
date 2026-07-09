<?php

namespace App\Modules\tao\Helper\Auth;

use App\Modules\tao\Helper\MyMvcHelper;
use App\Modules\tao\Models\SystemUser;
use Phax\Foundation\AppService;

abstract class LoginAuthAdapter
{
    protected bool $is_redis_adapter = false;
    protected string $session_prefix = '';
    protected int $lifetime = 0;

    public function __construct(public MyMvcHelper $mvc)
    {
        $this->is_redis_adapter = $mvc->session()->getAdapter() instanceof \Phalcon\Session\Adapter\Redis;
        if ($this->is_redis_adapter) {
            $this->session_prefix = AppService::config()->getString('session.stores.redis.prefix');
            $this->lifetime = AppService::config()->getInt('session.stores.redis.lifetime');
        }
    }

    abstract public static function check(MyMvcHelper $mvc): bool;

    abstract public function data();

    /**
     * 获取登录用户信息
     * @return SystemUser|null
     */
    abstract public function getUser(): SystemUser|null;

    /**
     * 保存用户信息
     * @param SystemUser $user
     * @param array $info 其它的配置信息
     * @return mixed 登录标识 token/jwtToken 其它
     */
    abstract public function saveUser(SystemUser $user,array $info = []): mixed;

    /**
     * 退出登录
     * @return void
     */
    abstract public function destroy(): void;
}