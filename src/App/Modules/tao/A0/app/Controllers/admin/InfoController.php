<?php

namespace App\Modules\tao\A0\app\Controllers\admin;


use App\Modules\tao\A0\app\Models\AppInfo;

/**
 * @property AppInfo $model
 */
class InfoController extends \App\Modules\tao\BaseController
{
    protected string $htmlTitle = '应用信息';

    protected array|string $superAdminActions = '*';
    protected array $appendModifyFields = ['title'];

    public function afterInitialize(): void
    {
        $this->model = new AppInfo();
    }

    protected array $saveWhiteList = [
        'tag', 'title', 'remark'
    ];

}