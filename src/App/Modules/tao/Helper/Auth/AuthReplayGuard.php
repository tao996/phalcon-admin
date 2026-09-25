<?php

namespace App\Modules\tao\Helper\Auth;

use Phax\Foundation\AppService;
use Phax\Support\Exception\BusinessException;

/**
 * v2 请求 nonce 防重放存储。
 *
 * v2 签名要求 Redis 可用；服务不可用时拒绝请求，避免降级为可重放模式。
 */
class AuthReplayGuard
{
    public function claim(string $token, string $nonce, int $ttl): bool
    {
        $key = 'auth:nonce:' . hash('sha256', $token . "\0" . $nonce);
        try {
            $claimed = AppService::redis()->set(
                $key,
                '1',
                ['nx', 'ex' => max(1, $ttl)]
            );
        } catch (\Throwable $e) {
            throw new BusinessException('请求防重放服务暂不可用', [], 503);
        }

        return (bool) $claimed;
    }
}
