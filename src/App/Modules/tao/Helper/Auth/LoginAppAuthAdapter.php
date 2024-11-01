<?php

namespace App\Modules\tao\Helper\Auth;

use App\Modules\tao\Helper\MyMvcHelper;
use App\Modules\tao\Models\SystemUser;

use Phax\Utils\MyData;

/**
 * 通常用于小程序 mini 对请求进行加密
 */
class LoginAppAuthAdapter extends LoginAuthAdapter
{

    public array $options = ['EX' => 604800]; // 默认缓存 7 天

    private array $data;

    public static function check(MyMvcHelper $mvc): bool
    {
        return $mvc->request()->hasHeader('Authorization');
    }

    /**
     * @throws \Exception
     */
    public function data(): void
    {
        $authData = $this->mvc->request()->getHeader('Authorization');
        if (!empty($authData)) {
            $this->data = json_decode($authData, true);
            try {
                MyData::mustHasSet($this->data, ['token', 't', 'sign']);
            } catch (\Exception $e) {
                throw new \Exception('登录凭证过期或不存在.', 403);
            }
        }
    }

    /**
     * @throws \RedisException
     * @throws \Exception
     */
    public function getUser(): SystemUser|null
    {
        if (!empty($this->data['token'])) {
            $userId = $this->mvc->authRedisData()->getUserId($this->data['token'], 'app');
            if ('logout' != $this->mvc->route()->getAction()) {
                $secret = $this->mvc->authRedisData()->getTokenValue($this->data['token']);
                if (!$secret) {
                    throw new \Exception('登录凭证过期或不存在', 403);
                }; // 用于签名的 secret
                // 包含了毫秒数的时间戳（时间戳本身也具有验签作用）

                $timestamp = intval($this->data['t']);
                $sign = md5($secret . $timestamp);
                if ($sign !== MyData::getString($this->data, 'sign')) {
                    throw new \Exception('签名验证失败');
                }
            }
            // 刷新 token 时间
            if ($user = SystemUser::findFirst($userId)) {
                // 太过频繁刷新
                if ($this->mvc->authRedisData()->getTtl($this->data['token']) < 3600 * 24) {
                    $this->mvc->authRedisData()->setTokenExpire(
                        $this->data['token'],
                        $this->options['EX'] ?? 3600 * 24
                    );
                }
                return $user;
            }
        }
        return null;
    }

    private function getCacheToken(int $userId): string
    {
        return $this->mvc->authRedisData()
            ->generateToken($userId, 'app'); // 已经使用了 . 号作为分割号
    }

    public function saveUser(array $user): mixed
    {
        $userId = MyData::getInt($user, 'id');
        if ($userId < 1) {
            throw new \Exception('user id is empty when save user');
        }
        $token = $this->getCacheToken($userId);
        // 随机码，用于生成 sign 签名
        $sec = md5(join(',', [rand(1, 100), time() + rand(100, 9999)]));
        $this->mvc->authRedisData()->setToken($token, $sec, ['EX' => 604800]); // 24*3600*7 = 7 天
        return join('-', [$token, $sec]);
    }

    public function destroy(): void
    {
        if (isset($this->data['token'])) {
            $this->mvc->authRedisData()->delToken($this->data['token']);
        }
    }

}