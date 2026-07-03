<?php

namespace App\Modules\demo\A0\db\Controllers\user;

use App\Modules\tao\Helper\Libs\RBAC;
use Phax\Mvc\Controller;

#[RBAC(title: 'demo.Info')]
class InfoController extends Controller
{
    /**
     * @link http://localhost:8071/api/m/demo.db/user.info/name
     * @return string[]
     */
    #[RBAC(title: 'name', close: 1)]
    public function nameAction(): array
    {
        return [
            'name' => 'pha....'
        ];
    }
}