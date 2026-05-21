<?php

namespace App\Modules\demo\A0\db\Controllers\user;

use Phax\Mvc\Controller;

/**
 * @rbac ({title:'Info'})
 */
class InfoController extends Controller
{
    /**
     * @rbac ({title:'name',close:1})
     */
    public function nameAction()
    {
        return [
            'name' => 'pha....'
        ];
    }
}