<?php

namespace App\Modules\tao;

use Phalcon\Filter\Exception;
use Phax\Db\QueryBuilder;

use Phax\Db\Transaction;
use Phax\Mvc\Model;
use Phax\Support\Validate;
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
     * 追加可修改的字段，默认为 $allowModifyFields
     * @var array|string[]
     */
    protected array $appendModifyFields = [];
    /**
     * 当前控制器所使用的模型（用于增删改查）
     * @var Model|null
     */
    protected Model|null $model = null;
    /**
     * @var Model|null 用于保存被修改或被删除的模型
     */
    protected Model|null $oldModel = null;
    /**
     * @var bool 是否需要保存修改的模型，如果为 true，在 editAction 和 deleteAction 时，会保存修改的模型
     */
    protected bool $keepOldModel = false;
    /**
     * @var bool 是否支持批量删除
     */
    protected bool $allowBatchDelete = false;


    /**
     * @throws \Exception
     */
    public function initialize(): void
    {
        parent::initialize();
        parent::rbacInitialize();
        $this->afterInitialize();
    }

    /**
     * 通常用于子控制器做一些初始化工作
     * @return void
     */
    protected function afterInitialize(): void
    {
    }

    /**
     * 列表搜索结果允许显示的字段
     * @var string|array
     */
    protected string|array $modelQueryColumns = '';
    /**
     * 列表搜索结果不允许显示的字段
     * @var string|array
     */
    protected string|array $modelHiddenColumns = '';
    /**
     * 列表搜索时排序条伯
     * @var string
     */
    protected string $modelOrderBy = 'id desc';

    /**
     * @var string 設置 HTML 頁面名称
     */
    protected string $htmlTitle = '';


    /**
     * 处理查询语句，通常用来补充默认的查询条件(modelQueryColumns/modelHiddenColumns/modelOrderBy)
     * @param QueryBuilder $queryBuilder
     * @return void
     * @throws \Exception
     */
    protected function beforeIndexQuery(QueryBuilder $queryBuilder): void
    {
        if ($this->modelQueryColumns) {
            $queryBuilder->columns($this->modelQueryColumns);
        } elseif ($this->modelHiddenColumns) {
            $columns = $this->vv->metadata()->getAttributes($this->model);
            $queryBuilder->columns(
                array_diff(
                    $columns,
                    is_array($this->modelHiddenColumns)
                        ? $this->modelHiddenColumns
                        : explode(',', $this->modelHiddenColumns)
                )
            );
        }
        if ($this->modelOrderBy) {
            $queryBuilder->orderBy($this->modelOrderBy);
        }
        if ($this->isResetSearch()) {
            return;
        }

        $this->actionQuery($queryBuilder);
    }

    /**
     * 用于子类追加查询条件
     * @param QueryBuilder $queryBuilder
     * @return void
     */
    protected function actionQuery(QueryBuilder $queryBuilder):void
    {
        if ($this->request->hasQuery('status') && property_exists($this->model, 'status')) {
            $queryBuilder->int('status', $this->request->getQuery('status', 'int', 0));
        }
    }


    /**
     * 获取查询记录
     * @param int $count 记录总数
     * @param QueryBuilder $queryBuilder
     * @return array 一次性将查询结果全部取出
     */
    protected function buildIndexResult(int $count, QueryBuilder $queryBuilder): array
    {
        return $queryBuilder->find();
    }

    /**
     * 获取全部查询结果，通常用在报表中
     * @return \Phalcon\Mvc\Model\Resultset\Simple|null
     * @throws \Exception
     */
    protected function getIndexResultset($queryBuilderCallback = null): \Phalcon\Mvc\Model\Resultset\Simple|null
    {
        $queryBuilder = $this->model->getQueryBuilder($this->getDI());
        $this->beforeIndexQuery($queryBuilder);
        if (is_callable($queryBuilderCallback)) {
            $queryBuilderCallback($queryBuilder);
        }
        return $queryBuilder->findModels();
    }

    /**
     * @rbac ({title:"数据列表"})
     * @return mixed
     * @throws \Exception
     */
    public function indexAction()
    {
        if ($this->isApiRequest()) {
            if ($this->model) {
                $queryBuilder = $this->model->getQueryBuilder($this->getDI());

                if ($this->isUserAction()) {
                    if (property_exists($this->model, 'user_id')) {
                        $queryBuilder->int('user_id', $this->loginUser()->id);
                    }
                }

                $this->beforeIndexQuery($queryBuilder);
                $count = $queryBuilder->count();
                $rows = [];
                if ($count > 0) {
                    $this->pagination($queryBuilder);
                    $rows = $this->buildIndexResult($count, $queryBuilder);
                }
                return $this->successPagination($count, $rows);
            } else {
                return $this->successPagination(0, []);
            }
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
     * 获取 POST 请求数据，优先使用 JSON body（小程序），否则使用表单数据
     * @return array
     */
    protected function getPostData(): array
    {
        return empty($this->requestData) ? $this->request->getPost() : $this->requestData;
    }

    /**
     * @rbac ({title:'添加记录'})
     */
    public function addAction()
    {
        if ($this->request->isPost()) {
            $data = $this->getPostData();

            if (property_exists($this->model, 'user_id')) {
                // 隐式调用 Phalcon Model 的 __set 魔术方法，IDE 绝不会报错
                $this->model->{'user_id'} = $this->loginUser()->id;
            }

            try {
                \Phax\Db\Transaction::db($this->db, function () use ($data) {
                    if (!$this->save($data)) {
                        throw new \Exception($this->model->getErrors());
                    }
                    $this->afterModelChange('add');
                });
            } catch (\Throwable $e) {
                return $this->error($e->getMessage());
            }
            return $this->success('添加成功', $this->model?->toArray());
        }
        $this->updateHtmlTitle('添加');
        return [];
    }

    /**
     * @rbac ({title:'编辑记录'})
     * @return mixed
     * @throws \Exception
     */
    public function editAction()
    {
        $id = $this->getRequestQueryInt('id');
        $this->model = $this->model::mustFindFirst($id);
        if ($this->keepOldModel) {
            $this->oldModel = clone $this->model;
        }
        $this->checkModelActionAccess($this->model);

        if ($this->request->isPost()) {
            $data = $this->getPostData();
            try {
                \Phax\Db\Transaction::db($this->db, function () use ($data) {
                    if (!$this->save($data)) {
                        throw new \Exception($this->model->getErrors());
                    }
                    $this->afterModelChange('edit');
                });
            } catch (\Throwable $e) {
                return $this->error($e->getMessage());
            }
            return $this->success('修改成功', $this->model?->toArray());
        }
        $this->updateHtmlTitle('编辑');
        return $this->beforeEditView($this->model->toArray());
    }

    /**
     * 视图渲染前修改数据
     * @param array $data
     * @return array
     */
    protected function beforeEditView(array $data): array
    {
        return $data;
    }

    /**
     * 处理保存到模型的数据，在 addAction/editAction 中，`$this->model->assign` 之前被调用;
     * 此时 `$this->model` 已经被实例化，但未赋值给模型属性
     * @param array $data 将要保存到模型中的数据
     * @return array 保存到模型中的数据
     */
    protected function beforeModelAssign(array $data): array
    {
        foreach ($this->model->floatColumns as $column) {
            if (array_key_exists($column, $data)) {
                $data[$column] = (float)$data[$column];
            } else {
                $data[$column] = 0;
            }
        }
        foreach ($this->model->nullColumns as $column) {
            if (array_key_exists($column, $data) && in_array($data[$column], ['', 0])) {
                $data[$column] = null;
            }
        }
        foreach ($this->model->bool2IntColumns as $column) {
            if (array_key_exists($column, $data)) {
                $data[$column] = MyData::getBool($data, $column) ? 1 : 0;
            } else {
                $data[$column] = 0;
            }
        }
        foreach ($this->model->intColumns as $column) {
            if (array_key_exists($column, $data)) {
                $data[$column] = MyData::getInt($data, $column);
            } else {
                $data[$column] = 0;
            }
        }
        if ($this->model != null) {
            if (property_exists($this->model, 'rules')) {
                $v = new Validate($this->vv);
                $v->check($data, $this->model->rules);
            }
        }

        return $data;
    }

    /**
     * 在模型保存到数据库之前 `$this->model->save()` 时调用
     * @return void
     */
    protected function beforeModelSave(): void
    {
    }

    /**
     * 在 addAction/editAction 中被调用
     * 1. 会触发 `beforeModelAssign` 回调方法 <br>
     * 2. 调用模型 `assign` 方法 <br>
     * 3. 触发  `beforeModelSave` 回调方法 <br>
     * @param array $data 保存到模型中的数据
     * @return bool
     */
    protected function save(array $data): bool
    {
        $data = $this->beforeModelAssign($data);
        if ($data) {
            $this->model->assign($data);
        }
        $this->beforeModelSave();
        return $this->model->save();
    }

    /**
     * modifyAction 校验钩子，对提交的数据进行检查
     * 在执行 `($this->model)::mustFindFirst($post['id'])` 之前调用
     * @param array $data
     * @return void
     */
    protected function beforeModifyData(array $data)
    {
    }

    /**
     * 在 modifyAction 保存数据 `$model->save()` 之前调用
     * @param $model
     * @return void
     */
    protected function beforeModelModifySave(Model $model): void
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
        $post = $this->getPostData();
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
        $this->beforeModifyData($post);


        $this->model = ($this->model)::mustFindFirst($post['id']);
        if ($this->keepOldModel) {
            $this->oldModel = clone $this->model;
        }
        $this->checkModelActionAccess($this->model);

        $this->model->assign([
            $post['field'] => $post['value']
        ], [$post['field']]);
        $this->beforeModelModifySave($this->model);

        try {
            Transaction::db($this->db, function (\Phalcon\Db\Adapter\Pdo\AbstractPdo $db) {
                if ($this->model->save()) {
                    $this->afterModelChange('edit');
                } else {
                    throw new \Exception($this->model->getErrors());
                }
            });
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
        $this->vv->logService()->insert($this->model->tableTitle(), 'modify');
        return $this->success('保存成功');
    }

    /**
     * 删除指定记录前执行，通常用于检查是否能够删除
     * @param QueryBuilder $queryBuilder
     * @param array $ids
     * @return void
     */
    protected function beforeDeleteQuery(QueryBuilder $queryBuilder, array $ids)
    {
    }

    /**
     * 批量删除成功之后立即执行
     * @param array $ids
     * @return void
     */
    protected function afterBatchDelete(array $ids)
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
        if (!$this->allowBatchDelete && count($ids) > 1) {
            return $this->error('批量删除功能已关闭');
        }

        // 批量删除
        $qb = $this->model->getQueryBuilder($this->getDI())->in('id', $ids);
        if ($this->isUserAction()) {
            if (property_exists($this->model, 'user_id')) {
                $qb->int('user_id', $this->loginUser()->id);
            }
        }
        if ($this->keepOldModel) {
            if (count($ids) == 1) {
                $this->oldModel = ($this->model)::mustFindFirst($ids[0]);
            }
        }

        $this->beforeDeleteQuery($qb, $ids);
        try {
            \Phax\Db\Transaction::db($this->db, function () use ($qb, $ids) {
                if (!$qb->delete()) {
                    throw new \Exception('删除失败');
                }
                $this->afterBatchDelete($ids);
                $this->afterModelChange('delete');
            });
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
        $this->vv->logService()->insert($this->model->tableTitle(), 'delete');
        return $this->success('删除成功');
    }

    /**
     * 检查用户修改记录的权限，并确保普通用户只能操作自己的记录
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
                // 普通用户：校验记录归属自己，而非强制覆盖
                if ($model->user_id != $this->loginUser()->id) {
                    throw new \Exception('没有修改记录的权限');
                }
            } elseif ($this->isSuperAdminAction()) {
                if (!$this->vv->userRecordAccess($this->loginUser()->id, $model->user_id)) {
                    throw new \Exception('没有修改记录的权限');
                }
            } else {
                // 检查是否有修改节点的权限
                if (!$this->vv->loginUserHelper()->access($this->vv->route()->getNode())) {
                    throw new \Exception('没有修改记录的权限');
                }
            }
        }
    }

    /**
     * 在模型增删改之后进行 `success/error` 响应
     * @param bool $success 如果为 true，则会调用 afterModelChange 方法
     * @param string $action 操作类型
     * @return array
     */
    protected function saveModelResponse(bool $success, string $action = 'edit'): array
    {
        static $actions = ['add' => '添加', 'edit' => '修改'];
        if (!isset($actions[$action])) {
            throw new \Exception('未知操作类型');
        }
        $text = $actions[$action];
        if ($success) {
            $this->afterModelChange($action);
        }
        return $success
            ? $this->success($text . '成功', $this->model?->toArray())
            : $this->error($text . '失败:' . $this->model?->getFirstError());
    }

    /**
     * 在模型被 add/edit|delete 成功之后被调用；注意：删除操作(批量)只作通知用；如果需要触发删除事件，重写 afterDelete
     * 当 $keepOldModel=true 且 action=edit|delete 时，$this->oldModel 为修改/删除前的模型快照；
     * 批量删除时 $this->oldModel 为 null（仅单条删除时可用）
     * @param string $action add|edit|delete
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
        $v = $this->getRequest($name, 'int', 0);
        if ($notAllowEmpty && empty($v)) {
            throw new \Exception($name . ' is empty');
        }
        return intval($v);
    }

    /**
     * @throws \Exception
     */
    public function getRequestInts(string $name, bool $notAllowEmpty = true): array
    {
        $v = $this->getRequest($name);
        if ($notAllowEmpty && empty($v)) {
            throw new \Exception($name . ' is empty');
        }
        return MyData::getInts($v);
    }

    /**
     * 获取请求参数
     * @param string|null $name 参数名，null 时返回全部
     * @param string|null $filters 过滤器（如 'int', 'string', 'email' 等），参见 Phalcon\Filter
     * @param mixed $default 默认值
     * @return mixed
     * @throws Exception
     */
    public function getRequest(?string $name = null, ?string $filters = null, mixed $default = null): mixed
    {
        if ($this->jsonBodyRequest) {
            if (is_null($name)) {
                return $this->requestData;
            }
            $value = $this->requestData[$name] ?? $default;
            if ($filters !== null && $value !== null && $value !== $default) {
                $value = $this->filter->sanitize($value, $filters);
            }
            return $value;
        }
        return $this->request->get($name, $filters, $default);
    }

    protected function mustHasOldModel(): void
    {
        if ($this->oldModel == null) {
            throw new \Exception('原始记录模型为空');
        }
    }
}