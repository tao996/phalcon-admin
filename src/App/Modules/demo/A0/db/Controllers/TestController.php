<?php

namespace App\Modules\demo\A0\db\Controllers;

use App\Modules\demo\Models\Cat;
use App\Modules\demo\Models\User;
use Phax\Mvc\Controller;

/**
 * @rbac ({title:'DbTest'})
 */
class TestController extends Controller
{
    public function indexAction()
    {
        /**
         * @var $user User
         */
        $user = User::findFirst(1);

        return [
            'user' => $user->toArray(),
            'user.articles => hasMany' => $user->articles()->toArray(),
            'user.profile => hasOne' => $user->profile()->toArray(),
            'user.roles => hasManyToMany' => $user->roles()->toArray(),
        ];
    }

    /**
     * @rbac ({title:'时间戳和添加记录'})
     */
    public function insertAction()
    {
        $this->vv->isDemo(true);
        $cat = new Cat();
        if (!$cat->assign([
            'name' => 'gray',
            'title' => '小灰',
            'age' => rand(1, 100)
        ])->save()) {
            throw new \Exception($cat->getFirstError());
        }
        return $cat->toArray();
    }


    /**
     * 软删除
     * @link http://localhost:8071/api/m/demo/index/remove
     * @return void
     * @throws \Exception
     */
    public function removeAction()
    {
        $this->vv->isDemo(true);
        $cats = Cat::findOnlyTrashed();
        ddd($cats->getFirst()->isDelete(), $cats->toArray());

        /**
         * @var $cat Cat
         */
//        $cat = Cat::findFirst();
//        pr($cat?->toArray(), false); // toArray(['name', 'title'])
//        ddd($cat->delete());
    }

    /**
     * 记录查询
     * @return void
     */
    public function listAction()
    {
        $p = Cat::queryBuilder(false)
            ->excludeColumns(['created_at', 'updated_at']);
        $this->json([
            'all' => $p->find(),
            'active' => $p->softDelete()->find()
        ]);
//        ddd('全部记录', $p->find(), '有效记录:', $p->softDelete()->find());
    }

    /**
     * 表单验证
     */
    public function formAction()
    {
        if ($this->request->isPost()) {
            if ($this->vv->security()->checkToken()) {
                $this->vv->validate()->check($this->request->getPost(), [
                    'accept' => 'accepted',
                    'email' => 'eq:abc@test.com',
                ], [
                    'accept.accepted' => '必须接受条款',
                    'email.eq' => '邮箱必须为 abc@test.com'
                ]);
                $this->flash->success('验证通过');
            } else {
                $this->flash->error('token 失败');
            }
        }
        return [];
    }

}