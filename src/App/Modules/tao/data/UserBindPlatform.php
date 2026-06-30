<?php

namespace App\Modules\tao\Data;

/**
 * 第三方平台绑定常量
 *
 * 统一管理所有支持的第三方平台类型，替代分散在
 * Data::MapBinds 和 Config::MapPlatform/MapKinds 中的定义。
 */
class UserBindPlatform
{
    const string Gmail = 'gmail';
    const string WechatMini = 'wechatMini';
    const string WechatOfficial = 'wechatOfficial';
    const string TiktokMini = 'tiktokMini';

    // 开放平台应用类型（原 Config::MapKinds）
    const string AppGzh = 'gzh';
    const string AppDyh = 'dyh';
    const string AppFwh = 'fwh';
    const string AppWeb = 'web';
    const string AppMini = 'mini';
    const string AppWork = 'work';

    // 平台编号（原 Config::MapPlatform）
    const int PlatformWechat = 1;
    const int PlatformTiktok = 2;

    /**
     * 绑定类型 → 显示名称
     */
    const array Map = [
        self::Gmail => 'Google',
        self::TiktokMini => '抖音小程序',
        self::WechatMini => '微信小程序',
        self::WechatOfficial => '微信公众号',
    ];

    /**
     * 开放平台应用类型 → 显示名称
     */
    const array MapAppKinds = [
        self::AppGzh => '公众号',
        self::AppDyh => '订阅号',
        self::AppFwh => '服务号',
        self::AppWeb => '网页应用',
        self::AppMini => '小程序',
        self::AppWork => '企业微信',
    ];

    /**
     * 平台编号 → 显示名称
     */
    const array MapPlatform = [
        self::PlatformWechat => '微信',
        self::PlatformTiktok => '抖音',
    ];

    /**
     * 验证绑定类型是否有效
     */
    public static function isValid(string $platform): bool
    {
        return isset(self::Map[$platform]);
    }
}
