<?php

namespace App\Modules\tao\A0\open;

use App\Modules\tao\A0\open\Helper\MyOpenMvcHelper;
use App\Modules\tao\BaseController;

abstract class BaseOpenController extends BaseController
{
    public MyOpenMvcHelper $mvc;

    protected function afterInitialize(): void
    {
        $this->mvc = new MyOpenMvcHelper($this->vv);
        $this->localInitialize();
    }

    abstract protected function localInitialize(): void;
}