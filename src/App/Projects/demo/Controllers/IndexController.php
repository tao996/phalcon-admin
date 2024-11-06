<?php

namespace App\Projects\demo\Controllers;

use Phax\Mvc\Controller;

class IndexController extends Controller
{
    public function indexAction()
    {
        return [
            'name' => 'Phalcon'
        ];
    }
}