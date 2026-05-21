<?php

namespace App\Http\Controllers\sub;

use Phax\Mvc\Controller;

/**
 * @rbac ({title:'Test1'})
 */
class TestController extends Controller
{
    /**
     * @rbac ({title:'ABC'})
     */
    public function abcAction()
    {
        return [
            'data' => 'ABC',
        ];
    }
}