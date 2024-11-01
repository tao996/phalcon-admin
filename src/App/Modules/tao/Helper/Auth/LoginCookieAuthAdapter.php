<?php

namespace App\Modules\tao\Helper\Auth;

use App\Modules\tao\Helper\MyMvcHelper;
use App\Modules\tao\Models\SystemUser;
use Phax\Support\Logger;
use Phax\Utils\MyData;


/**
 * 从 cookie 中读取凭证（目前用于 web 端）
 */
class LoginCookieAuthAdapter extends LoginAuthAdapter
{
    public int $expireSeconds = 3600;
    private string $authValue = '';

    public static function check(MyMvcHelper $mvc): bool
    {
        return $mvc->cookies()->has('Authorization');
    }

    public function data(): void
    {
        $this->authValue = $this->mvc->$this->mvc->cookies()
            ->get('Authorization')
            ->getValue('string', '');
    }

    /**
     * @throws \Exception
     */
    public function getUser(): SystemUser|null
    {
        if ($this->authValue) {
            $userId = $this->mvc->authRedisData()->getUserId($this->authValue, 'web');
            $data = $this->mvc->authRedisData()->getTokenValue($this->authValue);
            if ($data != 1) {
                Logger::info('CookieAuth 当前登录凭证不存在或已过期:' . $this->authValue);
                return null;
            }

            if ($user = SystemUser::findFirst($userId)) {
                $this->mvc->authRedisData()->setTokenExpire($this->authValue, $this->expireSeconds);
                return $user;
            }
        }
        return null;
    }

    public function saveUser(array $user): mixed
    {
        $userId = MyData::getInt($user, 'id');
        $token = join(':', [$userId, 'web', time()]); // 由 3 部分组成
        // 可以设置保存用户的设备信息
        $this->mvc->authRedisData()->setToken($token, 1, ['EX' => $this->expireSeconds]); // 默认 1 个小时
        $this->mvc->responseHelper()->cookieSet('Authorization', $token);
        return $token;
    }

    public function destroy(): void
    {
        if ($this->authValue) {
            $this->mvc->authRedisData()->delToken($this->authValue);
            $this->mvc->cookies()->get('Authorization')->delete();
        }
        $this->mvc->responseHelper()->cookieRemove();
    }
}