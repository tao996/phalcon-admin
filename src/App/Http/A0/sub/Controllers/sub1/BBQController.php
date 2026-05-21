<?php

namespace App\Http\A0\sub\Controllers\sub1;

use Phax\Mvc\Controller;

/**
 * @rbac ({})
 */
class BBQController extends Controller
{
    /**
     * @rbac ({})
     * @return void
     */
    public function sayAction()
    {
        ddd('just for test RBAC');
    }
}