<?php

namespace App\Modules\tao\Helper\Auth;

use App\Modules\tao\Config\Config;
use App\Modules\tao\Models\SystemUser;

use App\Modules\tao\Services\UserService;
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

    /**
     * Redis 凭证的默认有效期，实际值由 app.auth_token_ttl 配置。
     */
    public array $options = [];

    public function __construct()
    {
        parent::__construct();
        $this->options = ['EX' => Config::appAuthTokenTtl()];
    }

    private array $data = [];

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
            $decoded = json_decode($authData, true);
            if (!is_array($decoded)) {
                throw new BusinessException('登录凭证格式错误', [], 401);
            }
            $this->data = $decoded;

            $required = ['token'];
            if ('logout' != AppService::context()->getActionName()) {
                $required[] = 't';
                $required[] = 'sign';
            }
            try {
                MyAssert::mustHasSet($this->data, $required);
            } catch (\Exception $e) {
                throw new BusinessException('登录凭证过期或不存在.', [], 401);
            }
        }
    }

    /**
     * @throws \RedisException
     * @throws \Exception
     */
    public function getUser(): SystemUser|null
    {
        if (empty($this->data['token'])) {
            return null;
        }

        $token = $this->data['token'];
        $userId = TaoAppService::authRedisData()->getUserId($token, 'app');
        $verifySignature = 'logout' != AppService::context()->getActionName();

        if ($verifySignature) {
            $secret = TaoAppService::authRedisData()->getTokenValue($token);
            if (!$secret) {
                throw new BusinessException('登录凭证过期或不存在', [], 401);
            }

            // 客户端使用秒级时间戳；Redis/DB 适配器均按此值验签。
            $timestamp = intval($this->data['t']);
            $sign = md5($secret . $timestamp);
            $clientSign = MyData::getString($this->data, 'sign');
            if (!hash_equals($sign, $clientSign)) {
                throw new BusinessException('签名验证失败', [
                    'timestamp' => $timestamp,
                ], 401);
            }
        }

        if ($user = SystemUser::findFirst($userId)) {
            if ($verifySignature) {
                UserService::activeStatus($user);

                // 只有账号和签名都有效后才续期。
                $ttl = TaoAppService::authRedisData()->getTtl($token);
                $lifetime = max(1, (int) ($this->options['EX'] ?? Config::appAuthTokenTtl()));
                if ($ttl <= 0 || $ttl < intdiv($lifetime, 2)) {
                    TaoAppService::authRedisData()->setTokenExpire($token, $lifetime);
                }
            }
            return $user;
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
        // 使用密码学安全随机数生成 128 bit secret，并以 hex 返回。
        $sec = bin2hex(random_bytes(16));
        $ex = max(1, MyData::getInt(
            $info,
            'EX',
            $this->options['EX'] ?? Config::appAuthTokenTtl()
        ));
        TaoAppService::authRedisData()->setToken($token, $sec, ['EX' => $ex]);
        return join('-', [$token, $sec]);
    }

    public function destroy(): void
    {
        if (isset($this->data['token'])) {
            TaoAppService::authRedisData()->delToken($this->data['token']);
        }
    }

}