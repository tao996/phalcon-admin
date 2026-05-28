<?php

namespace App\Modules\tao;

use Phalcon\Filter\Exception;
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
     * 处理查询语句，通常用来补充查询条件
     * @param QueryBuilder $queryBuilder
     * @return void
     * @throws \Exception
     */
    protected function beforeIndexQuery(QueryBuilder $queryBuilder): void
    {
        if ($this->isResetSearch()) {
            return;
        }
        if ($this->request->hasQuery('status') && property_exists($this->model, 'status')) {
            $queryBuilder->int('status', $this->request->getQuery('status', 'int', 0));
        }
    }

    /**
     * 处理搜索的结果，已经在 indexAction 中自动对 $count>0 作出判断
     * @param int $count 记录总数
     * @param QueryBuilder $queryBuilder
     * @return array
     */
    protected function buildIndexResult(int $count, QueryBuilder $queryBuilder): array
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
        return $queryBuilder->find();
    }

    /**
     * @rbac ({title:"数据列表"})
     * @return mixed
     * @throws \Exception
     */
    public function indexAction()
    {
        if ($this->isApiRequest()) {
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

            return $this->saveModelResponse($this->save($data), 'add');
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
        $this->checkModelActionAccess($this->model);

        if ($this->request->isPost()) {
            $data = $this->getPostData();
            return $this->saveModelResponse($this->save($data));
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
     * 校验钩子
     * 检查修改参数，在模型检查过后，执行 `($this->model)::mustFindFirst($post['id'])` 之前调用
     * @param array $data
     * @return void
     */
    protected function validateModifyData(array $data)
    {
    }

    /**
     * 在 modifyAction 保存数据 `$model->save()` 之前调用
     * @param $model
     * @return void
     */
    protected function beforeModifySave(Model $model): void
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
        $this->validateModifyData($post);

        /**
         * @var $model Model
         */
        $model = ($this->model)::mustFindFirst($post['id']);
        $this->checkModelActionAccess($model);

        $model->assign([
            $post['field'] => $post['value']
        ]);
        $this->beforeModifySave($model);
        if ($model->save()) {
            $this->vv->logService()->insert($model->tableTitle(), 'modify');
            $this->afterModelChange('edit');
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

        // 批量删除
        $qb = $this->model->getQueryBuilder($this->getDI())->in('id', $ids);
        if ($this->isUserAction()) {
            if (property_exists($this->model, 'user_id')) {
                $qb->int('user_id', $this->loginUser()->id);
            }
        }

        $this->beforeDeleteQuery($qb, $ids);
        if ($qb->delete()) {
            $this->afterBatchDelete($ids);
            $this->vv->logService()->insert($this->model->tableTitle(), 'delete');
            $this->afterModelChange('delete');
            return $this->success('删除成功');
        } else {
            return $this->error('删除失败');
        }
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
}