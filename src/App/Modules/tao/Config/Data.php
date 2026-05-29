<?php

namespace App\Modules\tao\Config;

class Data
{
    /**
     * 首页的 PID
     */
    const int HOME_PID = 99999999;

    const string Gmail = 'gmail';
    const string TiktokMini = 'tiktokMini';
    const string WechatMini = 'wechatMini';
    const string WechatOfficial = 'wechatOfficial';

    const array MapBinds = [
        self::Gmail => 'Google',
        self::TiktokMini => '抖音小程序',
        self::WechatMini => '微信小程序',
        self::WechatOfficial => '微信公众号',
    ];

    const string AccessUser = 'user'; // 用户可见
    const string AccessSuperAdmin = 'superAdmin';

    const array MapAccess = [
        self::AccessUser => '用户',
        self::AccessSuperAdmin => '超级管理员'
    ];

    const int STATUS_NORMAL = 1;
    const int STATUS_DISABLE = 0;
    const array MAP_STATUS = [
        self::STATUS_NORMAL => '正常',
        self::STATUS_DISABLE => '禁用'
    ];
}