<?php

namespace App\Modules\tao\Helper\Auth;

use App\Modules\tao\Helper\MyMvcHelper;
use App\Modules\tao\Models\SystemUser;

class LoginUnitTestAuthAdapter extends LoginAuthAdapter
{
    private int $userId = 0;
    private SystemUser $user;
    public const string HeaderKeyName = 'USER_ID';

    public static function check(MyMvcHelper $mvc): bool
    {
        return $mvc->request()->hasHeader(self::HeaderKeyName);
    }

    public function data(): void
    {
        $this->userId = (int)$this->mvc->request()->getHeader(self::HeaderKeyName);
    }

    public function getUser(): SystemUser|null
    {
        if (!empty($this->user)) {
            return $this->user;
        }
        if ($this->userId > 0) {
            if ($this->user = SystemUser::findFirst($this->userId)) {
                return $this->user;
            }
        }
        return null;
    }

    public function saveUser(array $user): mixed
    {
        $this->userId = $user['id'];
        if (!empty($this->user)) {
            $this->user->assign($user);
        }
        return $user['id'];
    }

    public function destroy(): void
    {
    }
}