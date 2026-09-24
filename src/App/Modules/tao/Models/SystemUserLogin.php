<?php

namespace App\Modules\tao\Models;

use App\Modules\tao\BaseTaoModel;

/**
 * 用户登录记录
 *
 * 用于持久化存储 App 登录凭证。updated_at 用于滑动有效期续期。
 *
 * @property int    $id
 * @property int    $user_id    用户 ID
 * @property string $token      登录 token
 * @property string $secret     签名密钥
 * @property string $kind       类型（app/web）
 * @property string $ip         登录 IP
 * @property string $useragent  客户端信息
 */
class SystemUserLogin extends BaseTaoModel
{
    public int $user_id = 0;
    public string $token = '';
    public string $secret = '';
    public string $kind = 'app';
    public string $ip = '';
    public string $useragent = '';

    public function tableTitle(): string
    {
        return '用户登录记录';
    }
}
