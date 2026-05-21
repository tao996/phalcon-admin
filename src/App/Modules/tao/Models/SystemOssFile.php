<?php

namespace App\Modules\tao\Models;

use App\Modules\tao\BaseTaoModel;

/**
 * 记录用户 OSS 上传的记录
 */
class SystemOssFile extends BaseTaoModel
{
    public const int DriverQiniu = 1;
    public const int DriverAliyun = 2;
    public const int DriverTencent = 3;

    public int $user_id = 0;
    public int $app_id = 0; // 应用来源
    public int $driver = 0; // OSS
    public string $bucket = '';
    public string $digest = '';
    public string $save_key = '';
    public int $status = 1;
}