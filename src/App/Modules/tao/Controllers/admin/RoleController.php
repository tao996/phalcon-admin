<?php

namespace App\Modules\tao\Controllers\admin;

use App\Modules\tao\BaseController;
use App\Modules\tao\Models\SystemRole;
use App\Modules\tao\Models\SystemRoleNode;
use Phax\Db\QueryBuilder;

/**
 * @rbac ({title:'角色管理'})
 * @property SystemRole $model
 */
class RoleController extends BaseController
{
    protected string $htmlTitle = '角色';

    public function afterInitialize(): void
    {
        $this->model = new SystemRole();
    }

    protected string|array $indexQueryColumns = 'id,name,title,sort,status,remark,created_at';

    protected function indexActionQueryBuilder(QueryBuilder $queryBuilder): void
    {
        $queryBuilder->int('status', $this->request->getQuery('status', 'int', 0));
        $queryBuilder->like('name', $this->request->getQuery('name', 'string'));
    }

    /**
     * @rbac ({title:'编辑角色'})
     * @throws \Exception
     */
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

            return $this->saveModelResponse($this->model->save(), false);
        }
        return $this->model->toArray();
    }

    /**
     * @rbac ({title:'添加角色'})
     */
    public function addAction()
    {
        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            $this->model->assign($data, ['title', 'name', 'remark']);
            return $this->saveModelResponse($this->model->create(), true);
        }
        return [];
    }

    /**
     * @rbac ({title:'角色授权'})
     * @throws \Exception
     */
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
                SystemRoleNode::queryBuilder()->int('role_id', $id)->delete();
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