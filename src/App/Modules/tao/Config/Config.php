<?php

namespace App\Modules\tao\Config;

use Phax\Foundation\AppService;
use Phax\Utils\MyData;

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

    public static function authConfig(): array
    {
        static $config = [];
        if (empty($config)) {
            $data = AppService::config()->getArray('app.modules.tao');
            $config = [
                'auth_adapter' => MyData::getString($data,'auth_adapter', 'redis'),
                'auth_token_ttl' => intval($data['auth_token_ttl'] ?? 31536000),
                'auth_max_login_records' => intval($data['auth_max_login_records'] ?? 0),
                'auth_timestamp_window' => intval($data['auth_timestamp_window'] ?? 300),
                'auth_replay_ttl' => max(intval($data['auth_timestamp_window'] ?? 600), self::authTimestampWindow() * 2),
                'auth_allow_legacy_signature' => boolval($data['auth_allow_legacy_signature'] ?? false),
            ];
        }
        return $config;
    }

    public static function authAdapter(): string
    {
        return self::authConfig()['auth_adapter'];
    }

    /**
     * App 登录凭证滑动有效期：默认 1 年。
     * 有效请求会在达到续期阈值后刷新有效期，主动 logout 仍然立即撤销凭证。
     */
    public static function appAuthTokenTtl(): int
    {
        return self::authConfig()['auth_token_ttl'];
    }

    /**
     * 每个用户最多保留的 App 登录记录数；0 表示不自动挤出旧设备。
     */
    public static function appAuthMaxLoginRecords(): int
    {
        return self::authConfig()['auth_max_login_records'];
    }

    /**
     * 是否允许旧版 v1 MD5 签名；完成客户端升级后可关闭。
     */
    public static function authAllowLegacySignature(): bool
    {
        return self::authConfig()['auth_allow_legacy_signature'];
    }

    /**
     * v2 请求签名允许的时间窗口（秒）。
     */
    public static function authTimestampWindow(): int
    {
        return self::authConfig()['auth_timestamp_window'];
    }

    /**
     * v2 nonce 防重放记录的保留时间（秒）。
     */
    public static function authReplayTtl(): int
    {
        return self::authConfig()['auth_replay_ttl'];
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