<?php

namespace App\Modules\tao\Controllers\admin;

use App\Modules\tao\BaseController;
use App\Modules\tao\Config\Data;
use App\Modules\tao\Helper\Libs\RBAC;
use App\Modules\tao\Models\SystemMenu;
use App\Modules\tao\Models\SystemNode;
use App\Modules\tao\sdk\phaxui\Layui\LayuiData;
use App\Modules\tao\Services\MenuService;
use Phax\Support\Exception\BusinessException;
use Phax\Support\Router;
use Phax\Utils\MyData;
use Phax\Db\QueryBuilder;

/**
 * @property SystemMenu $model
 */
#[RBAC(title: '菜单管理')]
class MenuController extends BaseController
{
    protected string $htmlTitle = '菜单';
    protected array|string $userActions = ['user'];

    protected array $allowModifyFields = ['sort', 'status', 'roles', 'remark', 'href', 'params', 'remark'];
    protected string|array $modelQueryColumns = [
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

    protected function buildIndexResult(int $count, QueryBuilder $queryBuilder): array
    {
        $rows = $queryBuilder->notEqual('pid', Data::HOME_PID)
            ->columns($this->modelQueryColumns)
            ->orderBy('pid asc, sort desc, id asc')
            ->disabledPagination()->find();

        foreach ($rows as $index => $item) {
            if ($item['href']) {
                $rows[$index]['href'] = MenuService::href($item['href'], $item['type'], $item['params']);
            }
        }
        return LayuiData::treeTable($rows);
    }

    #[RBAC(title: '添加菜单')]
    public function addAction()
    {
        $pid = $this->getRequestInt('pid', false);
        $homeId = MenuService::homeId();
        if ($pid == $homeId) {
            return $this->error('首页不能添加子菜单');
        }
        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            $data['id'] = 0;
            $this->saveData($this->model, $data);
            return $this->saveModelResponse($this->model->save(), 'add');
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
                if (str_starts_with($data['href'], Router::$modulePrefix)) {
                    throw new BusinessException('Module 链接地址不能以 /m/ 开头', [
                        'data' => $data, 'type' => 'module'
                    ]);
                }
            } else {
                if (SystemNode::KIND_PROJECT == $data['type']) {
                    if (str_starts_with($data['href'], Router::$projectPrefix)) {
                        throw new BusinessException('Project 链接地址不能以 ' . Router::$projectPrefix . ' 开头', [
                            'data' => $data, 'type' => 'project'
                        ]);
                    }
                }
            }
        } else {
            $data['kind'] = 0;
        }
        $data['sort'] = MyData::getInt($data, 'sort');
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

    #[RBAC(title: '编辑菜单')]
    public function editAction()
    {
        $id = $this->getRequestQueryInt('id');
        $this->model = SystemMenu::mustFindFirst($id);
        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            $this->saveData($this->model, $data);
            return $this->saveModelResponse($this->model->save());
        }

        return $this->model->toArray();
    }

    /**
     * 获取指定用户菜单列表
     * @param $userId
     * @return array
     */
    public function userAction($userId)
    {
        if ($userId < 1) {
            throw new BusinessException('user id 不能为空');
        }
        return $this->vv->loginUserHelper()->getMenuTree();
    }

    protected function beforeDeleteQuery(QueryBuilder $queryBuilder, array $ids)
    {
        $homeId = MenuService::homeId();
        if (in_array($homeId, $ids)) {
            throw new BusinessException('不允许删除后台首页');
        }
    }

}