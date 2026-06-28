<?php

namespace App\Modules\tao\Controllers\admin;

use App\Modules\tao\BaseController;
use App\Modules\tao\Helper\Libs\RBAC;
use App\Modules\tao\Models\SystemRole;
use App\Modules\tao\Models\SystemRoleNode;
use Phax\Db\QueryBuilder;

/**
 * @property SystemRole $model
 */
#[RBAC(title: '角色管理')]
class RoleController extends BaseController
{
    protected string $htmlTitle = '角色';

    public function afterInitialize(): void
    {
        $this->model = new SystemRole();
    }

    protected string|array $modelQueryColumns = 'id,name,title,sort,status,remark,created_at';

    protected function actionQuery(QueryBuilder $queryBuilder): void
    {
        parent::actionQuery($queryBuilder);
        $queryBuilder->like('name', $this->request->getQuery('name', 'string'));
    }

    /**
     * @throws \Exception
     */
    #[RBAC(title: '编辑角色')]
    public function editAction()
    {
        $id = $this->getRequestQueryInt('id');
        $this->model = SystemRole::findFirst($id);
        $this->checkModelActionAccess($this->model);

        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            $this->model->assign($data, [
                'title',
                'name',
                'remark',
            ]);

            return $this->saveModelResponse($this->model->save());
        }
        return $this->model->toArray();
    }

    #[RBAC(title: '添加角色')]
    public function addAction()
    {
        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            $this->model->assign($data, ['title', 'name', 'remark']);
            return $this->saveModelResponse($this->model->create(), 'add');
        }
        return [];
    }

    #[RBAC(title: '角色授权')]
    public function authorizeAction()
    {
        $id = $this->getRequestQueryInt('id'); // 角色 ID
        $role = SystemRole::findFirst($id);
        $this->checkModelActionAccess($role);

        if ($this->isApiRequest()) {
            if ($this->request->isGet()) {
                $list = $this->vv->nodeService()->getAuthorizeNodeListByRoleId($role->id);
                return $this->success('', $list);
            } elseif ($this->request->isPost()) {
                $nodes = $this->getRequestInts('node', false); // 授权节点
                // 移除原来的绑定
                SystemRoleNode::queryBuilder($this->getDI())
                    ->int('role_id', $id)->delete();
                if ($nodes) {
                    $rows = [];
                    foreach ($nodes as $nodeId) {
                        $rows[] = [$id, $nodeId];
                    }
                    SystemRoleNode::layer()->batchInsert($rows, ['role_id', 'node_id']);
                }

                return $this->success('保存授权成功');
            }
        }
        return $role->toArray();
    }


}