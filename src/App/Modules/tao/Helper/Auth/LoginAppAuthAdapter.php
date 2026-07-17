<?php

namespace App\Modules\tao\Helper\Auth;

use App\Modules\tao\Models\SystemUser;

use App\Modules\tao\TaoAppService;
use Phax\Foundation\AppService;
use Phax\Support\Exception\BusinessException;
use Phax\Utils\MyAssert;
use Phax\Utils\MyData;

/**
 * 通常用于小程序 mini 对请求进行加密
 */
class LoginAppAuthAdapter extends LoginAuthAdapter
{

    public array $options = ['EX' => 604800]; // 默认缓存 7 天

    private array $data;

    public static function check(): bool
    {
        return AppService::request()->hasHeader('Authorization');
    }

    /**
     * @throws \Exception
     */
    public function data(): void
    {
        $authData = AppService::request()->getHeader('Authorization');
        if (!empty($authData)) {
            $this->data = json_decode($authData, true);
            try {
                MyAssert::mustHasSet($this->data, ['token', 't', 'sign']);
            } catch (\Exception $e) {
                throw new BusinessException('登录凭证过期或不存在.', [
                    'data' => $this->data,
                ], 401);
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
            $userId = TaoAppService::authRedisData()->getUserId($this->data['token'], 'app');
            if ('logout' != AppService::routeContext()->getActionName()) {
                $secret = TaoAppService::authRedisData()->getTokenValue($this->data['token']);
                if (!$secret) {
                    throw new BusinessException('登录凭证过期或不存在', [
                        'data' => $this->data,
                    ], 403);
                }; // 用于签名的 secret
                // 包含了毫秒数的时间戳（时间戳本身也具有验签作用）

                $timestamp = intval($this->data['t']);
                $sign = md5($secret . $timestamp);
//                ddd($secret,$timestamp,$sign,MyData::getString($this->data, 'sign'),'aaa');
                if ($sign !== MyData::getString($this->data, 'sign')) {
                    throw new BusinessException('签名验证失败', [
                        'data' => $this->data,
                        'timestamp' => $timestamp, 'expect' => $sign
                    ]);
                }
            }
            // 刷新 token 时间
            if ($user = SystemUser::findFirst($userId)) {
                // 太过频繁刷新
                if (TaoAppService::authRedisData()->getTtl($this->data['token']) < 3600 * 24 * 2) {
                    TaoAppService::authRedisData()->setTokenExpire(
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
        return TaoAppService::authRedisData()
            ->generateToken($userId, 'app'); // 已经使用了 . 号作为分割号
    }

    public function saveUser(SystemUser $user, array $info = []): mixed
    {
        $userId = $user->id;
        $token = $this->getCacheToken($userId);
        // 随机码，用于生成 sign 签名
        $sec = md5(join(',', [rand(1, 100), time() + rand(100, 9999)]));
        $ex = MyData::getInt($info, 'EX', 604800);
        TaoAppService::authRedisData()->setToken($token, $sec, ['EX' => $ex]); // 24*3600*7 = 7 天
        return join('-', [$token, $sec]);
    }

    public function destroy(): void
    {
        if (isset($this->data['token'])) {
            TaoAppService::authRedisData()->delToken($this->data['token']);
        }
    }

}