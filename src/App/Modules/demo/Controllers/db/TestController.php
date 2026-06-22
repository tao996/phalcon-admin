<?php

namespace App\Modules\demo\Controllers\db;

use App\Modules\demo\Models\Cat;

use Phax\Db\Transaction;
use Phax\Mvc\Controller;

/**
 * @rbac ({title:'DbTest'})
 */
class TestController extends Controller
{
    /**
     * @rbac ({title:'HELLO'})
     */
    public function helloAction(): void
    {
        $this->view->setVars(['name' => 'phax admin']);
    }

    /**
     * @rbac ({title:'RBAC事务'})
     */
    public function transAction(): void
    {
        $this->vv->isDemo(true);

        Transaction::db(function () {
            $cat = Cat::findFirst(1);
            pr('cat 1 age+10', $cat->toArray(), false);
            $cat->age += 10;
            if ($cat->save() === false) {
                throw new \Exception($cat->getFirstError());
            }
            $cat2 = Cat::findFirst(2);
            pr('cat 2 age+5', $cat2->toArray(), false);
            $cat2->age += 5;
            if ($cat2->save() === false) {
                throw new \Exception($cat2->getFirstError());
            }
            throw new \Exception('异常，取消事务');
        });
    }
}