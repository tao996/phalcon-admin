<?php

namespace App\Http\Controllers;

use App\Modules\tao\Helper\Libs\RBAC;
use Phax\Mvc\Controller;

#[RBAC(title:'Home', close: 1)]
class IndexController extends Controller
{
    #[RBAC(title:'Index')]
    public function indexAction()
    {
    }

    public function aboutAction(string $name = 'Phalcon', int $age = 0)
    {
        return [
            'name' => $name,
            'age' => $age
        ];
    }
}