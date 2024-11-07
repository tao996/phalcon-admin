<?php

namespace App\Modules\tao\Controllers\admin;

use App\Modules\tao\BaseController;
use App\Modules\tao\Config\Data;
use App\Modules\tao\Models\SystemMenu;
use App\Modules\tao\Models\SystemNode;
use App\Modules\tao\sdk\phaxui\Layui\LayuiData;
use Phax\Support\Router;
use Phax\Utils\MyData;
use Phax\Db\QueryBuilder;

/**
 * @rbac ({title:'菜单管理'})
 * @property SystemMenu $model
 */
class MenuController extends BaseController
{
    protected string $htmlTitle = '菜单';

    protected array $allowModifyFields = ['sort', 'status', 'roles', 'remark', 'href', 'params', 'remark'];
    protected string|array $indexQueryColumns = [
        'id',
        'pid',
        'title',
        'icon',
        'href',
        'type',
        'sort',
        'status',
        'roles',
        'params'
    ];

    public function afterInitialize(): void
    {
        $this->model = new SystemMenu();
    }

    protected function indexActionGetResult(int $count, QueryBuilder $queryBuilder): array
    {
        $rows = $queryBuilder->notEqual('pid', Data::HOME_PID)
            ->columns($this->indexQueryColumns)
            ->orderBy('pid asc, sort desc, id asc')
            ->disabledPagination()->find();

        foreach ($rows as $index => $item) {
            if ($item['href']) {
                $rows[$index]['href'] = $this->vv->menuService()->href($item['href'], $item['type'], $item['params']);
            }
        }

        return LayuiData::treeTable($rows);
    }

    /**
     * @rbac ({title:'添加菜单'})
     * @throws \Exception
     */
    public function addAction()
    {
        $pid = $this->getRequestInt('pid', false);
        $homeId = $this->vv->menuService()->homeId();
        if ($pid == $homeId) {
            return $this->error('首页不能添加子菜单');
        }
        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            $data['id'] = 0;
            $this->saveData($this->model, $data);
            return $this->saveModelResponse($this->model->save(),true);
        }
        return [];
    }

    private function saveData(SystemMenu $model, array $data)
    {
        $this->vv->validate()->check($data, [
            'pid|上级菜单' => 'require',
            'title|菜单名称' => 'require',
        ]);

        $data['type'] = MyData::getInt($data, 'type', 0);
        if (!empty($data['href'])) {
            if (SystemNode::KIND_MODULE == $data['type']) {
                if (Router::isMultipleModules($data['href'])) {
                    throw new \Exception('Module 链接地址不能以 /m/ 开头');
                }
            } else {
                if (SystemNode::KIND_PROJECT == $data['type']) {
                    if (Router::isAppProject($data['href'])) {
                        throw new \Exception('Project 链接地址不能以 /p/ 开头');
                    }
                }
            }
        } else {
            $data['kind'] = 0;
        }
        $model->assign($data, [
            'pid',
            'title',
            'href',
            'icon',
            'type',
            'sort',
            'remark',
            'roles',
        ]);
    }

    /**
     * @rbac ({title:'编辑菜单'})
     * @throws \Exception
     */
    public function editAction()
    {
        $id = $this->getRequestQueryInt('id');
        $this->model = SystemMenu::mustFindFirst($id);
        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            $this->saveData($this->model, $data);
            return $this->saveModelResponse($this->model->save(),false);
        }

        return $this->model->toArray();
    }

    /**
     * 获取指定用户菜单列表
     * @param $userId
     * @return array
     * @throws \Exception
     */
    public function userAction($userId)
    {
        if ($userId < 1) {
            throw new \Exception('user id 不能为空');
        }
        return $this->vv->loginUserHelper()->getMenuTree();
    }

    protected function deleteActionBefore(QueryBuilder $queryBuilder, array $ids)
    {
        $homeId = $this->vv->menuService()->homeId();
        if (in_array($homeId, $ids)) {
            throw new \Exception('不允许删除后台首页');
        }
    }

}