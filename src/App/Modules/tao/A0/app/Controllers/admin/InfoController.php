<?php

namespace App\Modules\tao\A0\app\Controllers\admin;


use App\Modules\tao\A0\App\Models\AppInfo;

/**
 * @property AppInfo $model
 */
class InfoController extends \App\Modules\tao\BaseController
{
    protected string $htmlName = '应用信息';

    protected array|string $superAdminActions = '*';
    protected array $appendModifyFields = ['title'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new AppInfo();
    }

    protected array $saveWhiteList = [
        'tag', 'title', 'remark'
    ];

}