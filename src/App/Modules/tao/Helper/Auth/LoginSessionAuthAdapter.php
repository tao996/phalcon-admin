<?php

namespace App\Modules\tao\Helper\Auth;

use App\Modules\tao\Models\SystemUser;
use Phax\Foundation\AppService;

class LoginSessionAuthAdapter extends LoginAuthAdapter
{
    private const string Key = 'user_id';

    public static function check(): bool
    {
        return true;
    }

    public function data(): void
    {
    }

    public function getUser(): SystemUser|null
    {
        if ($userId = AppService::session()->get(self::Key, 0)) {
            if ($this->is_redis_adapter) {
                // 写入一个标记触发 session adapter 的 write()，由其内部机制刷新 TTL
                AppService::session()->set('_touch', time());
            }
            // 每小时最多刷新一次 cookie，避免每次请求都 setcookie
            $lastRefresh = (int)AppService::session()->get('_cookie_refresh', 0);
            if (time() - $lastRefresh > 3600) {
                $lifetime = AppService::config()->getInt('session.cookie_lifetime', 86400);
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
                AppService::session()->set('_cookie_refresh', time());
            }
            return SystemUser::findFirst($userId);
        }
        return null;
    }

    public function saveUser(SystemUser $user,array $info = []): mixed
    {
        AppService::session()->set(self::Key, $user->id);
        return join(':', [$user->id, 'web', time()]);
    }

    private function updateToken($userId)
    {
    }

    public function destroy(): void
    {
        AppService::session()->destroy();
    }
}