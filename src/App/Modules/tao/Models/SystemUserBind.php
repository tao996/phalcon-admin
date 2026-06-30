<?php

namespace App\Modules\tao\Models;

use App\Modules\tao\BaseTaoModel;

/**
 * 第三方平台绑定
 *
 * 替代 SystemUser.$binds JSON 字段，将每个绑定存储为独立行，
 * 支持按平台类型查询、数据完整性约束。
 *
 * @property int    $id
 * @property int    $user_id       用户 ID
 * @property string $platform      平台类型（gmail/wechatMini/tiktokMini/...）
 * @property string $open_id       第三方 open_id
 * @property string $union_id      第三方 union_id（可选）
 * @property string $nickname      第三方昵称
 * @property string $avatar        第三方头像
 * @property string $raw_data      原始返回数据（JSON）
 */
class SystemUserBind extends BaseTaoModel
{
    public int $user_id = 0;
    public string $platform = '';
    public string $open_id = '';
    public string $union_id = '';
    public string $nickname = '';
    public string $avatar = '';
    public ?string $raw_data = null;

    public function tableTitle(): string
    {
        return '用户第三方绑定';
    }
}
