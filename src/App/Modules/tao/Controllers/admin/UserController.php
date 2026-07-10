<?php

namespace App\Modules\tao\Controllers\admin;

use App\Modules\tao\BaseController;

use App\Modules\tao\Helper\Libs\RBAC;
use App\Modules\tao\Models\SystemRole;
use App\Modules\tao\Models\SystemUser;
use App\Modules\tao\Models\SystemUserBind;
use App\Modules\tao\sdk\phaxui\Layui\LayuiData;
use App\Modules\tao\Services\LogService;
use App\Modules\tao\Services\RoleService;
use App\Modules\tao\Services\UserService;
use App\Modules\tao\TaoAppService;
use Phax\Db\QueryBuilder;
use Phax\Foundation\AppService;
use Phax\Support\Exception\BusinessException;
use Phax\Utils\MyData;

/**
 * @property SystemUser $model
 */
#[RBAC(title: '用户管理')]
class UserController extends BaseController
{
    protected string $htmlTitle = '用户管理';

    public function afterInitialize(): void
    {
        $this->model = new SystemUser();
    }

    #[RBAC(title: '添加用户')]
    public function addAction()
    {
        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            $user = new SystemUser();
            UserService::newPassword(MyData::getString($data, 'password'), $user);
            UserService::mustUniqueEmail(MyData::getString($data, 'email'), $user, true);
            UserService::mustUniquePhone(MyData::getString($data, 'phone'), $user, true);
            if ($user->email) {
                $user->email_valid = (int)MyData::isTrueWith($data, 'email_valid');
            }
            if ($user->phone) {
                $user->phone_valid = (int)MyData::isTrueWith($data, 'phone_valid');
            }
            if (!UserService::hasLoginAccount($user)) {
                return $this->error('必须设置一个登录账号');
            }

            $user->head_img = MyData::getString($data, 'head_img');
            $user->signature = MyData::getString($data, 'signature');
            $user->nickname = MyData::getString($data, 'nickname');
            $user->role_ids = join(',', MyData::getIntsWith($data, 'role_ids'));
            $this->model = $user;
            return $this->saveModelResponse($this->model->create(),'add');
        }

        return [
            'auth_list' => RoleService::getActiveList()
        ];
    }

    protected string|array $modelQueryColumns = 'id,role_ids,head_img,nickname,email,email_valid,phone,phone_valid,status,created_at';

    protected function actionQuery(QueryBuilder $queryBuilder): void
    {
        parent::actionQuery($queryBuilder);
        $queryBuilder->int('id', $this->request->getQuery('id', 'int'))
            ->like('phone', $this->request->getQuery('phone', 'string'))
            ->like('email', $this->request->getQuery('email', 'string'));

        if ($tt = LayuiData::dateRange($this->request->getQuery('created_at'))) {
            $queryBuilder->between('created_at', $tt[0], $tt[1]);
        }
    }

    protected function buildIndexResult(int $count, QueryBuilder $queryBuilder): array
    {
        $rows = parent::buildIndexResult($count, $queryBuilder);
        // 批量查询绑定信息
        $userIds = array_column($rows, 'id');
        $bindsMap = [];
        if ($userIds) {
            $bindRows = SystemUserBind::queryBuilder()
                ->in('user_id', $userIds)
                ->findColumn('user_id, platform');
            foreach ($bindRows as $br) {
                $bindsMap[$br['user_id']][] = $br['platform'];
            }
        }
        $roleIds = [];
        foreach ($rows as $index => $row) {
            $row['binds'] = $bindsMap[$row['id']] ?? [];
            $row['role_ids'] = $row['role_ids'] ? explode(',', $row['role_ids']) : [];
            if (!empty($row['role_ids'])) {
                $roleIds = array_merge($roleIds, $row['role_ids']);
            }
            $rows[$index] = $row;
        }
        if ($roleIds) {
            $roleIds = array_unique(MyData::getInts($roleIds));
            $roles = SystemRole::queryBuilder($this->getDI())
                ->in('id', $roleIds)->int('status', 1)
                ->findColumn('id,name,title', 'id');
            foreach ($rows as $index => $row) {
                $row['roles'] = [];
                if (!empty($row['role_ids'])) {
                    foreach ($row['role_ids'] as $role_id) {
                        if (isset($roles[$role_id])) {
                            $row['roles'][] = $roles[$role_id];
                        }
                    }
                }
                unset($row['role_ids']);
                $rows[$index] = $row;
            }
        }
        return $rows;
    }

    #[RBAC(title: '编辑用户')]
    public function editAction()
    {
        $id = $this->getRequestQueryInt('id');
        /**
         * @var $user SystemUser
         */
        $user = SystemUser::findFirst($id);
        $this->checkModelActionAccess($user);

        if ($this->request->isPost()) {
            $data = $this->request->getPost();

            if (!empty($data['phone'])) {
                UserService::mustUniquePhone($data['phone'], $user, true);
                $user->phone_valid = (int)MyData::isTrueWith($data, 'phone_valid');
            }

            if (!empty($data['email'])) {
                UserService::mustUniqueEmail($data['email'], $user, true);
                $user->email_valid = (int)MyData::isTrueWith($data, 'email_valid');
            }

            $user->assign($data, ['nickname', 'signature', 'head_img']);

            // 非超级管理员才需要设置权限
            if (!UserService::isSuperAdmin($user)) {
                if (!empty($this->request->get('role_ids'))) {
                    $user->role_ids = join(',', MyData::getIntsWith($data, 'role_ids'));
                } else {
                    $user->role_ids = '';
                }
            } else {
                $user->role_ids = '';
            }
            $this->saveModelResponse($user->save());
        }

        $this->htmlTitle = '编辑会员';

        $data = $user->toArray();
        $data['role_ids'] = explode(',', $data['role_ids']);
        $data['auth_list'] = RoleService::getActiveList();
        return $data;
    }

    protected function beforeModifyData(array $data): void
    {
        if (in_array($data['id'], AppService::superAdminIds()) && $data['field'] == 'status') {
            throw new BusinessException('不允许修改超级管理员状态');
        }
    }

    protected function beforeDeleteQuery($queryBuilder, array $ids)
    {
        if (array_intersect(AppService::superAdminIds(), $ids)) {
            throw new BusinessException('不允许删除超级管理员');
        }
    }

    #[RBAC(title: '修改用户密码')]
    public function passwordAction()
    {
        $id = $this->getRequestInt('id'); // 用户 ID
        if (!TaoAppService::userRecordAccess($this->loginUser()->id,$id)){
            throw new BusinessException('没有修改密码的权限');
        }

        $data = $this->request->get();
        // 新密码与确认密码必须一致
        if ($this->request->isPost()) {
            MyData::mustHasSet($data, ['password'], ['old_password']);
        }
        $user = SystemUser::findFirst($id);
        $this->checkModelActionAccess($user);

        if ($this->request->isPost()) {
            // 不是超级管理员，则必须提供正确的旧密码
            if (!TaoAppService::loginUserHelper()->isSuperAdmin()) {
                if (!empty($user->password) && empty($data['old_password'])) {
                    throw new BusinessException('必须提供旧密码');
                }
                if (!empty($user->password)) {
                    if (!AppService::security()->checkHash($data['old_password'], $user->password)) {
                        throw new BusinessException('旧密码错误');
                    }
                }
            }
            UserService::newPassword($data['password'], $user);
            if ($user->save()) {
                LogService::insert($user->tableTitle(), '修改密码');
                return self::success('修改密码成功');
            } else {
                return self::error($user->getErrors());
            }
        }
        $this->htmlTitle = '修改密码';
        return [
            'user' => $user->toArray(),
        ];
    }
}