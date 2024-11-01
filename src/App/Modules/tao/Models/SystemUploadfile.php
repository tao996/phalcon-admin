<?php

namespace App\Modules\tao\Models;

use App\Modules\tao\BaseTaoModel;

class SystemUploadfile extends BaseTaoModel
{

    public int $user_id = 0;
    public string $upload_type = '';
    public string $summary = ''; //
    public string $url = '';
    public int $width = 0;
    public int $height = 0;
    public int $frames = 0; // 帧数/时长
    public string $mime_type = ''; // mime 类型
    public int $file_size = 0; // 文件大小
    public string $file_ext = ''; // 文件后缀
    public string $sha1 = ''; // 文件 sha1 编码

    // 非数据库属性
    public string $tmpSavePath = ''; // 文件在本地的保存路径

    public function tableTitle(): string
    {
        return '文件上传';
    }
}