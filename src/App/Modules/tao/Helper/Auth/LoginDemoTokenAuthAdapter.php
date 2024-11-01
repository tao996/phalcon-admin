<?php

namespace App\Modules\tao\Helper\Auth;

use App\Modules\tao\Helper\MyMvcHelper;
use App\Modules\tao\Models\SystemUser;

class LoginDemoTokenAuthAdapter extends LoginAuthAdapter
{
    private int $userId = 0;
    private array $users = [];
    public const string HeaderKeyName = 'testToken';

    public static function check(MyMvcHelper $mvc): bool
    {
        return $mvc->isTest() && $mvc->request()->hasHeader(self::HeaderKeyName);
    }

    public function data(): void
    {
        $authData = $this->mvc->request()->getHeader(self::HeaderKeyName);
        if ($authData) {
            $users = $this->mvc->config()->getTestUsers();
            if ($users) {
                $this->userId = $users[$authData] ?? 0;
                $this->users = $users;
            }
        }
    }

    public function getUser(): SystemUser|null
    {
        if ($this->userId > 0) {
            return SystemUser::findFirst($this->userId);
        }
        return null;
    }

    public function saveUser(array $user): mixed
    {
        $this->userId = $user['id'];
        return array_flip($this->users)[$this->userId] ?? '---test user not found---';
    }

    public function destroy(): void
    {
    }

}