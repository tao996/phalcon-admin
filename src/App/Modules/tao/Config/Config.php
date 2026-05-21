<?php

namespace App\Modules\tao\Config;

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
     * 登录后台后默认显示的界面
     */
    public static string $indexWelcome = 'tao/index/welcome';
}