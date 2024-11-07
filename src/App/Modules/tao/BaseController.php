<?php

namespace App\Modules\tao;

use App\Modules\tao\Config\Config;

use Phax\Db\QueryBuilder;

use Phax\Mvc\Model;
use Phax\Utils\MyData;

/**
 * 业务逻辑控制器，用于简便处理 index/add/edit/delete/modify 等操作
 */
class BaseController extends BaseRbacController
{
    /**
     * 允许修改的模型属性名称，需要傳入參數為 [id,field,value]
     * @var array|string[]
     */
    protected array $allowModifyFields = ['status', 'sort', 'remark'];
    /**
     * @var array|string[]
     */
    protected array $appendModifyFields = [];
    /**
     * 字段保存白名单
     * @var array
     */
    protected array $saveWhiteList = [];
    /**
     * 当前控制器所使用的模型（用于增删改查）
     * @var Model|null
     */
    protected Model|null $model = null;


    /**
     * @throws \Exception
     */
    public function initialize(): void
    {
        parent::initialize();
        parent::rbacInitialize();
        $this->afterInitialize();
    }

    protected function afterInitialize(): void
    {
    }

    /**
     * 列表搜索字段
     * @var string|array
     */
    protected string|array $indexQueryColumns = '';
    /**
     * 列表搜索隐藏的字段
     * @var string|array
     */
    protected string|array $indexHiddenColumns = '';
    /**
     * 列表搜索条件
     * @var string
     */
    protected string $indexOrder = 'id desc';

    /**
     * @var string 設置 HTML 頁面名称
     */
    protected string $htmlTitle = '';


    /**
     * 处理查询语句，通常用来补充查询条件
     * @param QueryBuilder $queryBuilder
     * @return void
     * @throws \Exception
     */
    protected function indexActionQueryBuilder(QueryBuilder $queryBuilder): void
    {
        if ($this->isResetSearch()) {
            return;
        }
        if (!$this->request->hasQuery('status') && property_exists($this->model, 'status')) {
            $queryBuilder->int('status', $this->request->getQuery('status', 'int', 0));
        }
    }

    /**
     * 处理搜索的结果，已经在 indexAction 中自动对 $count>0 作出判断
     * @param int $count 记录总数
     * @param QueryBuilder $queryBuilder
     * @return array
     */
    protected function indexActionGetResult(int $count, QueryBuilder $queryBuilder): array
    {
        if ($this->indexQueryColumns) {
            $queryBuilder->columns($this->indexQueryColumns);
        } elseif ($this->indexHiddenColumns) {
            $columns = $this->vv->metadata()->getAttributes($this->model);
            $queryBuilder->columns(
                array_diff(
                    $columns,
                    is_array($this->indexHiddenColumns)
                        ? $this->indexHiddenColumns
                        : explode(',', $this->indexHiddenColumns)
                )
            );
        }
        if ($this->indexOrder) {
            $queryBuilder->orderBy($this->indexOrder);
        }
        return $queryBuilder->find();
    }

    /**
     * @rbac ({title:"数据列表"})
     * @throws \Exception
     */
    public function indexAction()
    {
        if ($this->isApiRequest()) {
            $queryBuilder = $this->model->getQueryBuilder();


            if ($this->isUserAction()) {
                if (property_exists($this->model, 'user_id')) {
                    $queryBuilder->int('user_id', $this->loginUser()->id);
                }
            }

            $this->indexActionQueryBuilder($queryBuilder);
            $count = $queryBuilder->count();
            $rows = [];
            if ($count > 0) {
                $this->pagination($queryBuilder);
                $rows = $this->indexActionGetResult($count, $queryBuilder);
            }
            return $this->successPagination($count, $rows);
        }
        $this->updateHtmlTitle('列表', false);
        return [];
    }

    /**
     * 页面标题
     * @param string $action 操作名称
     * @param bool $prefix 操作名称是放在 htmlName 前面，还是后面
     * @return void
     */
    private function updateHtmlTitle(string $action, bool $prefix = true): void
    {
        if (!$this->isApiRequest()) {
            if ($this->htmlTitle) {
                $this->addViewData(
                    'html_title',
                    $prefix
                        ? $action . $this->htmlTitle
                        : $this->htmlTitle . $action
                );
            }
        }
    }

    protected function beforeViewResponse(mixed $data)
    {
        if ($this->htmlTitle && !$this->view->getVar('html_title')) {
            $this->addViewData('html_title', $this->htmlTitle);
        }
        return parent::beforeViewResponse($data);
    }

    /**
     * @rbac ({title:'添加记录'})
     */
    public function addAction()
    {
        if ($this->request->isPost()) {
            $data = $this->request->getPost();

            if (property_exists($this->model, 'user_id')) {
                $this->model->user_id = $this->loginUser()->id;
            }

            $success = $this->save($data);
            if ($success) {
                $this->afterModelChange('add');
            }
            return $this->saveModelResponse($success, true);
        }
        $this->updateHtmlTitle('添加');
        return [];
    }

    /**
     * @rbac ({title:'编辑记录'})
     * @throws \Exception
     */
    public function editAction()
    {
        $id = $this->getRequestQueryInt('id');
        $this->model = $this->model::mustFindFirst($id);
        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            $success = $this->save($data);
            if ($success) {
                $this->afterModelChange('edit');
            }
            return $this->saveModelResponse($success, false);
        }
        $this->updateHtmlTitle('编辑');
        return $this->model->toArray();
    }

    /**
     * 处理保存到模型的数据，在 addAction/editAction 中被调用
     * @param array $data 将要保存到模型中的数据
     * @return array 保存到模型中的数据
     */
    protected function beforeModelSaveAssign(array $data): array
    {
        return $data;
    }

    /**
     * 在 addAction/editAction 中被调用
     * @param array $data 保存到模型中的数据
     * @return bool
     */
    protected function save(array $data): bool
    {
        $data = $this->beforeModelSaveAssign($data);
        if (!empty($data)) {
            if ($this->saveWhiteList) {
                $this->model->assign($data, $this->saveWhiteList);
            } else {
                $this->model->assign($data);
            }
        }
        return $this->model->save();
    }

    /**
     * 检查修改参数
     * @param array $data
     * @return void
     */
    protected function modifyActionCheckPostData(array $data)
    {
    }

    /**
     * 注意：并未判断 user_id
     * @rbac ({title:"属性快捷修改"})
     * @throws \Exception
     */
    public function modifyAction()
    {
        $this->mustPostMethod();
        $post = $this->request->getPost();
        MyData::mustHasSet($post, ['id', 'field'], ['value']);
        $rules = [
            'id|ID' => 'int',
            'field|字段' => 'require',
        ];
        $this->vv->validate()->check($post, $rules);
        $rows = array_merge($this->allowModifyFields, $this->appendModifyFields);

        if (!in_array($post['field'], $rows)) {
            return $this->error('该字段不允许修改');
        }
        if (empty($this->model)) {
            return $this->error('控制器 this.model 不能为空');
        }
        if (!property_exists($this->model, $post['field'])) {
            return $this->error('当前模型不存在此属性');
        }
        $this->modifyActionCheckPostData($post);

        /**
         * @var $model Model
         */
        $model = ($this->model)::mustFindFirst($post['id']);
        $this->checkModelActionAccess($model);

        $model->assign([
            $post['field'] => $post['value']
        ]);
        if ($model->save()) {
            $this->vv->logService()->insert($model->tableTitle(), 'modify');
            $this->afterModelChange('modify');
            return $this->success('保存成功');
        } else {
            return $this->error($model->getErrors());
        }
    }

    /**
     * 删除指定记录前执行，通常用于检查是否能够删除
     * @param QueryBuilder $queryBuilder
     * @param array $ids
     * @return void
     */
    protected function deleteActionBefore(QueryBuilder $queryBuilder, array $ids)
    {
    }

    /**
     * 删除成功之后执行
     * @param array $ids
     * @return void
     */
    protected function deleteActionAfterSuccess(array $ids)
    {
    }

    /**
     * @rbac ({title:"删除记录"})
     * @throws \Exception
     */
    public function deleteAction()
    {
        $this->mustPostMethod();
        $ids = $this->getRequestInts('id');

        if (empty($ids)) {
            return $this->error('待删除记录 ID 不能为空');
        }
        $qb = $this->model->getQueryBuilder()->in('id', $ids);

        if ($this->isUserAction()) {
            if (property_exists($this->model, 'user_id')) {
                $qb->int('user_id', $this->loginUser()->id);
            }
        }

        $this->deleteActionBefore($qb, $ids);
        if ($qb->delete()) {
            $this->deleteActionAfterSuccess($ids);
            $this->vv->logService()->insert($this->model->tableTitle(), 'delete');
            $this->afterModelChange('delete');
            return $this->success('删除成功');
        } else {
            return $this->error('删除失败');
        }
    }

    /**
     * 检查用户修改记录的权限
     * @param Model|null $model
     * @throws \Exception
     */
    protected function checkModelActionAccess(Model|null $model): void
    {
        if (empty($model)) {
            throw new \Exception('记录不存在');
        }
        if (property_exists($model, 'user_id')) {
            if ($this->isUserAction()) {
                $model->user_id = $this->loginUser()->id;
            } elseif ($this->isSuperAdminAction()) {
                if ($model->user_id != $this->loginUser()->id) { // 修改别人的记录
                    $superAdminIds = $this->vv->superAdminIds();
                    if (in_array($model->user_id, $superAdminIds)) {
                        if (in_array($this->loginUser()->id, $superAdminIds)) {
                            $recordUserIndex = array_search($model->user_id, $superAdminIds);
                            $userIndex = array_search($this->loginUser()->id, $superAdminIds);
                            if ($userIndex > $recordUserIndex) {
                                throw new \Exception('不能修改更高级别的超级管理员');
                            }
                            return;
                        }
                    }
                    throw new \Exception('没有修改超级管理员记录的权限');
                }
            } else {
                // 检查是否有修改节点的权限
                if (!$this->vv->loginUserHelper()->access($this->vv->route()->getNode())) {
                    throw new \Exception('没有修改记录的权限');
                }
            }
        }
    }

    protected function saveModelResponse(bool $success, bool $isCreate = true)
    {
        $text = $isCreate ? '创建' : '保存';
        return $success
            ? $this->success($text . '成功', $this->model?->toArray())
            : $this->error($text . '失败:' . $this->model?->getFirstError());
    }

    /**
     * 在模型修改成功之后调用
     * @param string $action add|edit|delete|modify
     * @return void
     */
    protected function afterModelChange(string $action): void
    {
    }

    /**
     * @throws \Exception
     */
    protected function getRequestQueryInt(string $name, bool $notAllowEmpty = true)
    {
        $v = $this->request->getQuery($name, 'int', 0, $notAllowEmpty);
        if ($notAllowEmpty && empty($v)) {
            throw new \Exception($name . ' is empty');
        }
        return $v;
    }

    /**
     * @throws \Exception
     */
    public function getRequestInt(string $name, bool $notAllowEmpty = true): int
    {
        $v = $this->request->get($name, 'int', 0);
        if ($notAllowEmpty && empty($v)) {
            throw new \Exception($name . ' is empty');
        }
        return $v;
    }

    /**
     * @throws \Exception
     */
    public function getRequestInts(string $name, bool $notAllowEmpty = true): array
    {
        $v = $this->request->get($name);
        if ($notAllowEmpty && empty($v)) {
            throw new \Exception($name . ' is empty');
        }
        return MyData::getInts($v);
    }
}