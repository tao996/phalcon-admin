<?php

namespace App\Modules\tao\A0\cms;

use App\Modules\tao\A0\cms\Helper\MyCmsMvcHelper;
use App\Modules\tao\BaseController;

class BaseTaoA0CmsController extends BaseController
{
    public MyCmsMvcHelper $helper;

    public function afterInitialize(): void
    {
        parent::afterInitialize();
        $this->helper = $this->vv->a0cmsHelper();
        $this->localInitialize();
    }

    protected function localInitialize()
    {

    }
}