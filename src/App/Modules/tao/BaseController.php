<?php

namespace App\Modules\tao;

use App\Modules\tao\sdk\phaxui\Layui\LayuiData;
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
     * 字段保存白名单，如果设置，则只有允许的字段可以修改
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
     * @rbac ({title:'添加记录'})
     * @return mixed
     */
    public function addAction()
    {
        if ($this->request->isPost()) {
            $data = empty($this->requestData) ? $this->request->getPost() : $this->requestData;

            if (property_exists($this->model, 'user_id')) {
                $this->model->user_id = $this->loginUser()->id;
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
        if ($this->request->isPost()) {
            $data = empty($this->requestData) ? $this->request->getPost() : $this->requestData;
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
     * 将 layui bool 转为 int 以保存到字符串
     * @var array
     */
    public array $modelBool2IntColumns = [];
    /**
     * 提交数据中需要转为 float 的数组
     * @var array
     */
    public array $modelFloatColumns = [];

    /**
     * 处理保存到模型的数据，在 addAction/editAction 中，`$this->model->assign` 之前被调用
     * @param array $data 将要保存到模型中的数据
     * @return array 保存到模型中的数据
     */
    protected function beforeModelAssign(array $data): array
    {
        if ($this->modelBool2IntColumns) {
            LayuiData::bool2Int($data, $this->modelBool2IntColumns);
        }
        if ($this->modelFloatColumns) {
            foreach ($this->modelFloatColumns as $column) {
                $data[$column] = (float)$data[$column];
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
     * @param array $data 保存到模型中的数据
     * @return bool
     */
    protected function save(array $data): bool
    {
        $data = $this->beforeModelAssign($data);
        if (!empty($data)) {
            if ($this->saveWhiteList) {
                $this->model->assign($data, $this->saveWhiteList);
            } else {
                $this->model->assign($data);
            }
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
    protected function beforeModifySave($model)
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
        $post = empty($this->requestData) ? $this->request->getPost() : $this->requestData;
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
    protected function beforeDeleteQuery(QueryBuilder $queryBuilder, array $ids)
    {
    }

    /**
     * 删除成功之后立即执行
     * @param array $ids
     * @return void
     */
    protected function afterDelete(array $ids)
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
        $qb = $this->model->getQueryBuilder($this->getDI())->in('id', $ids);

        if ($this->isUserAction()) {
            if (property_exists($this->model, 'user_id')) {
                $qb->int('user_id', $this->loginUser()->id);
            }
        }

        $this->beforeDeleteQuery($qb, $ids);
        if ($qb->delete()) {
            $this->afterDelete($ids);
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
                if (!$this->vv->userRecordAccess($this->loginUser()->id, $model->user_id)) {
                    throw new \Exception('没有修改记录的权限1');
                }
            } else {
                // 检查是否有修改节点的权限
                if (!$this->vv->loginUserHelper()->access($this->vv->route()->getNode())) {
                    throw new \Exception('没有修改记录的权限2');
                }
            }
        }
    }

    /**
     * 在模型增删改之后进行 `success/error` 响应
     * @param bool $success 如果为 true，则会调用 afterModelChange 方法
     * @param string $action ['add'] 操作类型
     * @return array
     */
    protected function saveModelResponse(bool $success, string $action = 'save'): array
    {
        static $actions = ['add' => '添加', 'insert' => '创建', 'save' => '保存', 'delete' => '删除', 'edit' => '修改'];
        $text = $actions[$action] ?? $action;
        if ($success && !empty($action)) {
            $this->afterModelChange($action);
        }
        return $success
            ? $this->success($text . '成功', $this->model?->toArray())
            : $this->error($text . '失败:' . $this->model?->getFirstError());
    }

    /**
     * 在模型修改成功之后调用，通常在 add/edit/delete/modify 之后被调用
     * 通过 $this->model 获取修改的模型对象
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