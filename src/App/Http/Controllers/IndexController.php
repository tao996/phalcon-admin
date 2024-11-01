<?php

namespace App\Http\Controllers;


use Phax\Mvc\Controller;

/**
 * @rbac ({title:'Home',close:1})
 */
class IndexController extends Controller
{
    /**
     * @rbac ({title:'Index'})
     */
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