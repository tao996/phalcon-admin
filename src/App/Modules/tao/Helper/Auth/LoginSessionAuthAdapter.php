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
                // 不要直接调用 redis()->expire()——共享 redis 连接的 _prefix('_phx_')
                // 与 session 存储前缀 '_ses_' 不匹配，会导致 expire 刷错 key
                $this->mvc->session()->set('_touch', time());
            }
            return SystemUser::findFirst($userId);
        }
        return null;
    }

    public function saveUser(array $user): mixed
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