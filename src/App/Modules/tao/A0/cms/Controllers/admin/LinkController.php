<?php

namespace App\Modules\tao\A0\cms\Controllers\admin;

use App\Modules\tao\A0\cms\BaseTaoA0CmsController;
use App\Modules\tao\A0\cms\Models\CmsLink;

/**
 * @property CmsLink $model
 * @rbac ({title:'链接管理'})
 */
class LinkController extends BaseTaoA0CmsController
{
    protected array $appendModifyFields = ['tag'];
    protected string $htmlTitle = '链接';

    public function afterInitialize(): void
    {
        parent::afterInitialize();
        $this->model = new CmsLink();
    }
}