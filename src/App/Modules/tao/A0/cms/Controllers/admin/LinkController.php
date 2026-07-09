<?php

namespace App\Modules\tao\A0\cms\Controllers\admin;

use App\Modules\tao\A0\cms\Models\CmsLink;
use App\Modules\tao\BaseController;
use App\Modules\tao\Helper\Libs\RBAC;

/**
 * @property CmsLink $model
 */
#[RBAC(title: '链接管理')]
class LinkController extends BaseController
{
    protected array $appendModifyFields = ['tag'];
    protected string $htmlTitle = '链接';

    public function afterInitialize(): void
    {
        $this->model = new CmsLink();
    }
}