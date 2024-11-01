<?php

namespace App\Modules\tao\A0\cms\Controllers\admin;


use App\Modules\tao\A0\cms\BaseTaoA0CmsController;
use App\Modules\tao\A0\cms\Models\CmsCategory;
use App\Modules\tao\sdk\phaxui\Layui\LayuiData;
use Phax\Db\QueryBuilder;
use Phax\Db\Transaction;
use Phax\Utils\MyData;

/**
 * @property CmsCategory $model
 * @rbac ({title:'栏目管理'})
 */
class CategoryController extends BaseTaoA0CmsController
{
    protected array $appendModifyFields = ['navbar', 'name', 'tag'];
    protected string $htmlName = '栏目';


    public function afterInitialize(): void
    {
        parent::afterInitialize();
        $this->model = new CmsCategory();
    }

    protected function indexActionGetResult(int $count, QueryBuilder $queryBuilder): array
    {
        $rows = $queryBuilder->orderBy('pid asc, sort desc, id asc')
            ->disabledPagination()->excludeColumns([
                'created_at',
                'updated_at',
                'deleted_at',
                ''
            ])
            ->find();
        return LayuiData::treeTable($rows);
    }

    public function getPidCategoryList()
    {
        return array_merge([
            [
                'id' => 0,
                'pid' => 0,
                'title' => '一级栏目'
            ]
        ], $this->cms->categoryService()->options());
    }

    /**
     * @rbac ({title:'添加栏目'})
     */
    public function addAction()
    {
        $pid = $this->getRequestInt('pid', false);

        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            $this->save($data);
            return $this->saveModelResponse(true, true);
        }
        return [
            'pid' => $pid,
            'categoryList' => $this->getPidCategoryList(),
        ];
    }

    /**
     * @rbac ({title:'修改栏目'})
     */
    public function editAction()
    {
        $id = $this->getRequestQueryInt('id');
        $this->model = CmsCategory::mustFindFirst($id);

        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            $this->save($data);
            return $this->saveModelResponse(true, false);
        }
        $row = $this->model->toArray();
        $row['content'] = $this->cms->contentService()->getContentById($this->model->content_id);
        $row['images'] = $this->vv->uploadfileService()->getImages($this->model->image_ids);

        return [
            'row' => $row,
            'categoryList' => $this->getPidCategoryList(),
        ];
    }

    protected function save($data): bool
    {
        $this->vv->validate()->check($data, [
            'title|栏目名称' => 'required',
            'kind|栏目类型' => 'required'
        ]);

        $this->model->kind = intval($data['kind']);
        if (!in_array($this->model->kind, array_keys(CmsCategory::mapKind()))) {
            throw new \Exception('暂不支持的栏目类型');
        }

        $this->model->assign($data, [
            'pid',
            'title',
            'name',
            'tag',
            'summary',
            'cover',
            'tag',
            'navbar',
            'sort',
            'status',
            'other',
            'image_ids'
        ]);
        if ($this->model->pid > 0) {
            if ($parentCategory = CmsCategory::findFirst(
                $this->model->pid,
                function (\Phalcon\Mvc\Model\Query\Builder $builder) {
                    $builder->columns('id,pids');
                }
            )) {
                $pids = $parentCategory->pids ? explode(',', $parentCategory->pids) : [];
                $pids[] = $this->model->pid;
                $this->model->pids = join(',', $pids);
            }
        }

        Transaction::db($this->vv->db(), function () use ($data) {
            if ($this->model->kind == CmsCategory::KindList) {
                if (!empty($data['content']) || $this->model->content_id > 0) {
                    $cc1 = $this->cms->contentService()->saveContentDataById(
                        $this->model->content_id,
                        MyData::getString($data, 'content', '')
                    );
                    $this->model->content_id = $cc1->id;
                }
            }
            if (!$this->model->save()) {
                throw new \Exception('保存栏目信息错误:' . $this->model->getFirstError());
            }
        });
        return true;
    }
}