<?php

namespace App\Modules\tao\Helper\Auth;

use App\Modules\tao\Helper\MyMvcHelper;
use App\Modules\tao\Models\SystemUser;

class LoginSessionAuthAdapter extends LoginAuthAdapter
{
    private const string Key = 'user_id';

    public static function check(MyMvcHelper $mvc): bool
    {
        return true;
    }

    public function data(): void
    {
    }

    public function getUser(): SystemUser|null
    {
        if ($userId = $this->mvc->session()->get(self::Key, 0)) {
            if ($this->is_redis_adapter) {
                // 写入一个标记触发 session adapter 的 write()，由其内部机制刷新 TTL
                $this->mvc->session()->set('_touch', time());
            }
            // 同步刷新 cookie 过期时间，防止 cookie 先于会话过期
            $lifetime = (int)$this->mvc->config()->path('session.cookie_lifetime', 86400);
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                session_id(),
                time() + $lifetime,
                $params['path'],
                $params['domain'],
                $params['secure'] ?? false,
                $params['httponly'] ?? true
            );
            return SystemUser::findFirst($userId);
        }
        return null;
    }

    public function saveUser(array $user,array $info = []): mixed
    {
        $this->mvc->session()->set(self::Key, $user['id']);
        return join(':', [$user['id'], 'web', time()]);
    }

    private function updateToken($userId)
    {
    }

    public function destroy(): void
    {
        $this->mvc->session()->destroy();
    }
}