<?php

namespace App\Modules\tao\Helper\Auth;

use App\Modules\tao\Helper\MyMvcHelper;
use Phax\Foundation\AppService;
use Phax\Support\Exception\BusinessException;


class AuthRedisData
{
    public function __construct(public MyMvcHelper $mvc)
    {
    }

    private function getRedisKey(string $token): string
    {
        // $this->mvc->route()->getProject('phax') 不需要，因为设置的时候可能没有应用名称
        return 'login:' . $token;
    }

    public function delToken(string $token): void
    {
        $gToken = $this->getRedisKey($token);
       AppService::redis()->del($gToken);
    }

    public function setToken(string $token, mixed $value, $options = null)
    {
        AppService::redis()->set($this->getRedisKey($token), $value, $options);
    }

    public function setTokenExpire(string $token, int $seconds)
    {
        AppService::redis()->expire($this->getRedisKey($token), $seconds);
    }

    /**
     * 获取token 的剩余时间
     * @param string $token
     * @return int seconds
     * @throws \RedisException
     */
    public function getTtl(string $token): int
    {
        return AppService::redis()->ttl($this->getRedisKey($token)) ?: 0;
    }

    public function getTokenValue(string $token)
    {
        $gToken = $this->getRedisKey($token);
        return AppService::redis()->get($gToken);
    }

    /**
     * 为指定用户生成一个 token
     * @param int $userId
     * @return string
     */
    public function generateToken(int $userId, string $kind): string
    {
        return join('.', [$userId, $kind, time()]);
    }

    public function getUserId(string $token, string $kind): int
    {
        $tokenData = explode($kind == 'app' ? '.' : ':', $token);
//        dd($kind,$token,$tokenData);
        if (count($tokenData) != 3) {
            throw new BusinessException('用户登录凭证错误', [
                'error'=>'count($tokenData) != 3',
                'token' => $token, 'kind' => $kind, 'tokenData' => $tokenData,
            ]);
        }

        if (intval($tokenData[0]) < 1 || $tokenData[1] != $kind) {
            throw new BusinessException('用户登录凭证错误',[
                'token'=>$token,'kind'=>$kind,'tokenData'=>$tokenData,
            ]);
        }

        return $tokenData[0];
    }
}