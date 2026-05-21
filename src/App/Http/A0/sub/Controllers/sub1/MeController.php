<?php

namespace App\Http\A0\sub\Controllers\sub1;

use Phax\Mvc\Controller;

class MeController extends Controller
{
    public function sayAction()
    {
        return [
            'name' => 'ME~~~~'
        ];
    }
}