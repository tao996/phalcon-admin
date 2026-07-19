<?php

namespace App\Modules\demo\A0\db\Controllers;

use App\Modules\demo\Models\Cat;
use App\Modules\demo\Models\User;
use App\Modules\tao\Helper\Libs\RBAC;
use Phax\Foundation\AppService;
use Phax\Mvc\Controller;
use Phax\Support\Exception\BusinessException;
use Phax\Support\Validate;

#[RBAC(title: 'demo.A0.DbTest')]
class TestController extends Controller
{
    /**
     * @link http://localhost:8071/api/m/demo.db/test/index
     * @return array
     */
    public function indexAction()
    {
        /**
         * @var $user User
         */
        $user = User::findFirst(1);

        return [
            'user' => $user->toArray(),
            'user.articles => hasMany' => $user->articles->toArray(),
            'user.profile => hasOne' => $user->profile->toArray(),
            'user.roles => hasManyToMany' => $user->roles->toArray(),
        ];
    }

    #[RBAC(title: '时间戳和添加记录')]
    public function insertAction()
    {
        AppService::isDemo(true);
        $cat = new Cat();
        if (!$cat->assign([
            'name' => 'gray',
            'title' => '小灰',
            'age' => rand(1, 100)
        ])->save()) {
            throw new BusinessException('保存 Cat 记录错误', [
                'errors' => $cat->getErrors(),
            ]);
        }
        return $cat->toArray();
    }


    /**
     * 软删除
     * @link http://localhost:8071/api/m/demo.db/test/remove
     * @return array
     * @throws \Exception
     */
    public function removeAction()
    {
        AppService::isDemo(true);
        $cats = Cat::findOnlyTrashed();
        $isDelete = $cats->getFirst()->isDelete();
        return ['isDelete' => $isDelete];
    }

    /**
     * 记录查询
     * @link http://localhost:8071/api/m/demo.db/test/list
     * @return void
     */
    public function listAction()
    {
        $p = Cat::queryBuilder($this->getDI())
            ->withTrashed()
            ->excludeColumns(['created_at', 'updated_at']);
        AppService::echoJsonData([
            'all' => $p->find(),
            'active' => $p->softDelete()->find()
        ]);
    }

    /**
     * 表单验证
     * @link http://localhost:8071/api/m/demo.db/test/form
     */
    public function formAction()
    {
        if ($this->request->isPost()) {
            if (AppService::security()->checkToken()) {
                Validate::checkData($this->request->getPost(), [
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