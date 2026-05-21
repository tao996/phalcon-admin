<?php

namespace App\Modules\tao\A0\open;

use App\Modules\tao\A0\open\Helper\MyOpenMvcHelper;
use App\Modules\tao\BaseController;

abstract class BaseOpenController extends BaseController
{
    public MyOpenMvcHelper $helper;

    protected function afterInitialize(): void
    {
        $this->helper = $this->vv->a0openHelper();
        $this->localInitialize();
    }

    abstract protected function localInitialize(): void;
}