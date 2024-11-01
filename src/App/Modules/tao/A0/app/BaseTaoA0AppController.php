<?php

namespace App\Modules\tao\A0\app;

use App\Modules\tao\A0\cms\Helper\MyCmsMvcHelper;
use App\Modules\tao\BaseController;

class BaseTaoA0AppController extends BaseController
{
    public MyCmsMvcHelper $cms;

    public function afterInitialize(): void
    {
        parent::afterInitialize();
        $this->cms = new MyCmsMvcHelper($this->vv);
    }
}