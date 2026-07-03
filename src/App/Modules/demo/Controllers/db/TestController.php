<?php

namespace App\Modules\demo\Controllers\db;

use App\Modules\demo\Models\Cat;

use App\Modules\tao\Helper\Libs\RBAC;
use Phax\Db\Transaction;
use Phax\Mvc\Controller;
use Phax\Support\Exception\BusinessException;
use Phax\Support\Exception\LogException;

/**
 * @rbac ({title:'DbTest'})
 */
#[RBAC(title: 'DbTest')]
class TestController extends Controller
{
    /**
     * @link http://localhost:8071/api/m/demo/db.test/hello
     * @return array
     */
    #[RBAC(title: 'hello')]
    public function helloAction(): array
    {
        return ['name' => 'phax admin'];
    }

    /**
     * @link http://localhost:8071/api/m/demo/db.test/trans
     * @return array
     */
    #[RBAC(title: 'RBAC事务')]
    public function transAction(): array
    {
        $this->vv->isDemo(true);

        Transaction::db(function () {
            $cat = Cat::findFirst(1);
            $cat->age += 10;
            if ($cat->save() === false) {
//                throw new LogException('更新 cat1 数据失败',[
//                    'errors'=> $cat->getErrors(),
//                    'data'=>$cat->toArray(),
//                ]);
                // 测试时使用 BusinessException 抛出异常
                throw new BusinessException($cat->getFirstError());
            }
            $cat2 = Cat::findFirst(2);
            $cat2->age += 5;
            if ($cat2->save() === false) {
//                throw new LogException('更新 cat2 数据失败',[
//                    'errors'=> $cat2->getErrors(),
//                    'data'=>$cat2->toArray(),
//                ]);
                throw new BusinessException($cat2->getFirstError());
            }
            throw new BusinessException('异常，取消事务');
        });
        return [];
    }
}