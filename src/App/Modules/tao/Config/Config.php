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
    const int VerifyCodeActiveSeconds = 900;
    /**
     * 输入错误3次即失效
     */
    const int VerifyCodeMaxErrorNum = 3;
    /**
     * 用户修改手机号+电子邮件每天允许发送验证码数量
     */
    const int MaxChangeAccountCodeNum = 3;
    /**
     * 每个注册账号每天允许发送的验证码数量
     */
    const int MaxRegisterCodeNum = 3;
    /**
     * 验证码登录的次数
     */
    const int MaxSigninCodeNum = 3;
    const int MaxResetPasswordCodeNum = 3;
    /**
     * 登录后台后默认显示的界面
     */
    public static string $indexWelcome = 'tao/index/welcome';
}