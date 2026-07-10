<?php

namespace App\Modules\tao\Helper\Auth;

use App\Modules\tao\Models\SystemUser;
use App\Modules\tao\TaoAppService;
use App\Modules\tao\utils\ResponseUtil;
use Phax\Foundation\AppService;
use Phax\Support\Logger;


/**
 * 从 cookie 中读取凭证（目前用于 web 端）
 */
class LoginCookieAuthAdapter extends LoginAuthAdapter
{
    public int $expireSeconds = 3600;
    private string $authValue = '';

    public static function check(): bool
    {
        return AppService::cookies()->has('Authorization');
    }

    public function data(): void
    {
        $this->authValue = AppService::cookies()
            ->get('Authorization')
            ->getValue('string', '');
    }

    /**
     * @throws \Exception
     */
    public function getUser(): SystemUser|null
    {
        if ($this->authValue) {
            $userId = TaoAppService::authRedisData()->getUserId($this->authValue, 'web');
            $data = TaoAppService::authRedisData()->getTokenValue($this->authValue);
            if ($data != 1) {
                if (IS_DEBUG) {
                    Logger::debug('CookieAuth 当前登录凭证不存在或已过期', [
                        'authValue' => $this->authValue,
                        'data' => $data
                    ]);
                }
                return null;
            }

            if ($user = SystemUser::findFirst($userId)) {
                TaoAppService::authRedisData()->setTokenExpire($this->authValue, $this->expireSeconds);
                return $user;
            }
        }
        return null;
    }

    public function saveUser(SystemUser $user, array $info = []): mixed
    {
        $token = join(':', [$user->id, 'web', time()]); // 由 3 部分组成
        // 可以设置保存用户的设备信息
        TaoAppService::authRedisData()->setToken($token, 1, ['EX' => $this->expireSeconds]); // 默认 1 个小时
        ResponseUtil::cookieSet('Authorization', $token);
        return $token;
    }

    public function destroy(): void
    {
        if ($this->authValue) {
            TaoAppService::authRedisData()->delToken($this->authValue);
            AppService::cookies()->get('Authorization')->delete();
        }
        ResponseUtil::cookieRemove();
    }
}