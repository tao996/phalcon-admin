<?php

namespace App\Modules\tao\Helper\Auth;

use App\Modules\tao\Config\Config;
use Phax\Support\Exception\BusinessException;

/**
 * App Authorization 签名校验。
 *
 * v1（无 v/alg 字段）继续使用旧 md5(secret + t)，保证旧客户端平滑升级。
 * v2 使用 HMAC-SHA256，并校验时间窗口和一次性 nonce。
 */
class AuthSignature
{
    public static function verify(array $data, string $secret): void
    {
        $rawVersion = $data['v'] ?? 1;
        $version = is_int($rawVersion)
            ? $rawVersion
            : (is_string($rawVersion) && ctype_digit($rawVersion)
                ? (int) $rawVersion
                : 0);
        if ($version === 1) {
            if (!Config::authAllowLegacySignature()) {
                throw new BusinessException('旧版签名协议已禁用', [], 401);
            }
            self::verifyLegacy($data, $secret);
            return;
        }
        if ($version !== 2 || ($data['alg'] ?? '') !== 'hmac-sha256') {
            throw new BusinessException('签名协议版本错误', [], 401);
        }

        $token = (string) ($data['token'] ?? '');
        $timestamp = self::timestamp($data['t'] ?? null);
        $window = Config::authTimestampWindow();
        if (abs(time() - $timestamp) > $window) {
            throw new BusinessException('请求时间戳已过期', [], 401);
        }

        $nonce = (string) ($data['nonce'] ?? '');
        if (!preg_match('/\A[a-fA-F0-9]{16,128}\z/', $nonce)) {
            throw new BusinessException('请求 nonce 无效', [], 401);
        }

        $signature = strtolower((string) ($data['sign'] ?? ''));
        $expected = hash_hmac(
            'sha256',
            self::canonical($token, $timestamp, $nonce),
            $secret
        );
        if (!hash_equals($expected, $signature)) {
            throw new BusinessException('签名验证失败', [], 401);
        }

        if (!(new AuthReplayGuard())->claim(
            $token,
            $nonce,
            Config::authReplayTtl()
        )) {
            throw new BusinessException('请求已重放', [], 401);
        }
    }

    private static function verifyLegacy(array $data, string $secret): void
    {
        $timestamp = self::timestamp($data['t'] ?? null);
        $expected = md5($secret . $timestamp);
        $signature = strtolower((string) ($data['sign'] ?? ''));
        if (!hash_equals($expected, $signature)) {
            throw new BusinessException('签名验证失败', [], 401);
        }
    }

    private static function timestamp(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }
        throw new BusinessException('请求时间戳无效', [], 401);
    }

    private static function canonical(string $token, int $timestamp, string $nonce): string
    {
        return $token . "\n" . $timestamp . "\n" . $nonce;
    }
}
