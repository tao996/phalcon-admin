<?php

namespace App\Modules\tao\Helper\Auth;

use App\Modules\tao\Helper\MyMvcHelper;
use App\Modules\tao\Models\SystemUser;

class LoginDemoTokenAuthAdapter extends LoginAuthAdapter
{
    private int $userId = 0;
    private array $users = [];
    public const string HeaderKeyName = 'test-token';

    public static function check(MyMvcHelper $mvc): bool
    {
        return $mvc->isTest() && $mvc->request()->hasHeader(self::HeaderKeyName);
    }

    public function data(): void
    {
        $authData = $this->mvc->request()->getHeader(self::HeaderKeyName);
//        pr(
//            $this->mvc->request()->getHeaders(),
//            ['authData' => $authData, 'users' => $this->mvc->config()->getTestUsers()]
//        );
        if ($authData) {
            $users = $this->mvc->config()->getTestUsers();
            if (isset($users[$authData])) {
                $this->userId = $users[$authData];
                $this->users = $users;
            } else {
                // 因为是 phpunit 所以直接 pr 输出，方便调试
                pr([
                    'error' =>
                        'check your test-token, could not find in app.test.tokens',
                    'tokens' => $users,
                    'test-token' => $authData
                ]);
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