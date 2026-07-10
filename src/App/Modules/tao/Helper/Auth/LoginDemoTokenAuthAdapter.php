<?php

namespace App\Modules\tao\Helper\Auth;

use App\Modules\tao\Models\SystemUser;
use Phax\Foundation\AppService;

class LoginDemoTokenAuthAdapter extends LoginAuthAdapter
{
    private int $userId = 0;
    private array $testUsers = [];
    public const string HeaderKeyName = 'test-token';

    public static function check(): bool
    {
        return AppService::isTest() && AppService::request()->hasHeader(self::HeaderKeyName);
    }

    public function data(): void
    {
        $authData = AppService::request()->getHeader(self::HeaderKeyName);
//        pr(
//            $this->mvc->request()->getHeaders(),
//            ['authData' => $authData, 'users' => $this->mvc->config()->getTestUsers()]
//        );
        if ($authData) {
            $users = AppService::config()->getTestUsers();
            if (isset($users[$authData])) {
                $this->userId = $users[$authData];
                $this->testUsers = $users;
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

    public function saveUser(SystemUser $user,array $info = []): mixed
    {
        $this->userId = $user->id;
        return array_flip($this->testUsers)[$this->userId] ?? '---test user not found---';
    }

    public function destroy(): void
    {
    }

}