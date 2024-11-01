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
            return SystemUser::findFirst($userId);
        }
        return null;
    }

    public function saveUser(array $user): mixed
    {
        $this->mvc->session()->set(self::Key, $user['id']);
        return join(':', [$user['id'], 'web', time()]);
    }

    public function destroy(): void
    {
        $this->mvc->session()->destroy();
    }
}