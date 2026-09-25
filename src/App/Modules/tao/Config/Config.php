<?php

namespace App\Modules\tao\Config;

use Phax\Foundation\AppService;

class Config
{
    /**
     * 数据表前缀
     */
    const string TABLE_PREFIX = 'tao_';

    /**
     * 验证码15 分钟内有效
     */
    public static int $verifyCodeActiveSeconds = 900;
    /**
     * 输入错误3次即失效
     */
    public static int $verifyCodeMaxErrorNum = 3;

    /**
     * App 登录凭证滑动有效期：默认 1 年。
     * 有效请求会在达到续期阈值后刷新有效期，主动 logout 仍然立即撤销凭证。
     */
    public static function appAuthTokenTtl(): int
    {
        $ttl = AppService::config()->getInt('app.auth_token_ttl', 31536000);
        return $ttl > 0 ? $ttl : 31536000;
    }

    /**
     * 每个用户最多保留的 App 登录记录数；0 表示不自动挤出旧设备。
     */
    public static function appAuthMaxLoginRecords(): int
    {
        return max(0, AppService::config()->getInt('app.auth_max_login_records', 0));
    }

    /**
     * 是否允许旧版 v1 MD5 签名；完成客户端升级后可关闭。
     */
    public static function authAllowLegacySignature(): bool
    {
        return AppService::config()->getBoolean('app.auth_allow_legacy_signature', true);
    }

    /**
     * v2 请求签名允许的时间窗口（秒）。
     */
    public static function authTimestampWindow(): int
    {
        $window = AppService::config()->getInt('app.auth_timestamp_window', 300);
        return $window > 0 ? $window : 300;
    }

    /**
     * v2 nonce 防重放记录的保留时间（秒）。
     */
    public static function authReplayTtl(): int
    {
        $ttl = AppService::config()->getInt('app.auth_replay_ttl', 600);
        return max($ttl, self::authTimestampWindow() * 2);
    }

    /**
     * 登录后台后默认显示的界面
     * 可以在 config.php 中 app.welcome 中指定
     */
    public static function indexWelcome(): string
    {
        $path = AppService::config()->getString('app.welcome');
        return $path ?: '/m/tao/index/welcome';
    }
}