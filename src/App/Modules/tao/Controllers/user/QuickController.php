<?php

namespace App\Modules\tao\Controllers\user;

use App\Modules\tao\BaseController;
use App\Modules\tao\Models\SystemQuick;

/**
 * @property SystemQuick $model
 */
class QuickController extends BaseController
{
    protected string $htmlTitle = '链接';

    protected array|string $userActions = '*';

    protected array $sort = [
        'sort' => 'desc',
        'id' => 'desc',
    ];

    public function afterInitialize(): void
    {
        $this->model = new SystemQuick();
    }

    protected array $allowModifyFields = [
        'sort',
        'title',
        'status',
        'href',
        'remark',
    ];

    protected function beforeModelAssign(array $data): array
    {
        $this->vv->validate()->check($data, [
            'href|链接地址' => 'require',
            'title|快捷名称' => 'require',
        ]);
        return $data;
    }
}