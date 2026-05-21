<?php

namespace App\Modules\tao\A0\open\Models;

use App\Modules\tao\BaseTaoModel;

class OpenUserOpenid extends BaseTaoModel
{
    public int $platform = 0; // 平台
    public string $appid = '';// 抖音/微信 appid
    public int $user_id = 0; // 绑定用户
    public string $openid = '';
    public string $unionid = '';
    public string $session_key = '';

    // 解密数据
    // https://developer.open-douyin.com/docs/resource/zh-CN/mini-app/develop/api/open-interface/user-information/tt-get-user-profile
    public string $avatar_url = ''; // 头像
    public string $nickname = ''; // 昵称
    public int $gender = 0; // 0 未知，1 男，2 女
    public string $city = '';
    public string $province = ''; // 省
    public string $country = ''; // 国家
    public string $language = '';

    // 关注公众号
    public int $sub = 0; // 是否关注公众号（1：是；2；取消）
    public int $sub_at = 0; // 关注/取消关注时间
}