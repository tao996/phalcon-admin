<?php

namespace App\Modules\tao\A0\cms\Controllers\admin;


use App\Modules\tao\A0\cms\BaseTaoA0CmsController;
use App\Modules\tao\A0\cms\Models\CmsArticle;
use Phax\Db\QueryBuilder;
use Phax\Db\Transaction;
use Phax\Utils\MyData;

/**
 * @property CmsArticle $model
 * @rbac ({title:'文章管理'})
 */
class ArticleController extends BaseTaoA0CmsController
{
    protected array $cateOptions = [];
    protected array $appendModifyFields = ['top'];
    protected string $htmlTitle = '文章';

    /**
     * @throws \Exception
     */
    public function afterInitialize(): void
    {
        parent::afterInitialize();
        $this->model = new CmsArticle();
        $this->cateOptions = $this->cms->categoryService()->options();
        if ($this->vv->loginUserHelper()->isSuperAdmin()) {
            $this->appendModifyFields = array_merge($this->appendModifyFields, ['hits', 'hot', 'cstatus']);
        }
    }

    protected function indexActionGetResult(int $count, QueryBuilder $queryBuilder): array
    {
        if ($cateId = $this->request->getQuery('cate_id', 'int', 0)) {
            $queryBuilder->int('cate_id', $cateId);
        }
        if ($cstatus = $this->request->getQuery('cstatus', 'int', 0)) {
            $queryBuilder->int('cstatus', $cstatus);
        }
        $rows = parent::indexActionGetResult($count, $queryBuilder);
        $cate = array_column($this->cateOptions, 'otitle', 'id');
        foreach ($rows as $index => $row) {
            if ($row['cate_id'] > 0) {
                $rows[$index]['cate_title'] = MyData::getString($cate, $row['cate_id']);
            }
        }
        return $rows;
    }

    /**
     * @rbac ({title:'文章列表'})
     */
    public function indexAction()
    {
        if (!$this->isApiRequest()) {
            $this->vv->setVar('options', $this->cateOptions);
        }
        return parent::indexAction();
    }

    /**
     * @rbac ({title:'添加文章'})
     */
    public function addAction()
    {
        if ($this->request->isPost()) {
            $data = $this->request->getPost();

            $this->vv->validate()->check($data, [
                'cate_id|栏目' => 'required',
                'title|标题' => 'required',
                'content|内容' => 'required'
            ]);
            $row = $this->cms->categoryService()->getRecord($data['cate_id'], ['kind']); // 查询栏目类型

            $this->model->assign([
                'user_id' => $this->loginUser()->id,
                'ip' => $this->request->getClientAddress(),
                'kind' => $row['kind'],
            ]);

            if (empty($data['author'])) {
                $data['author'] = '管理员';
            }
            $this->save($data);

            return $this->saveModelResponse(true, true);
        }
        return [
            'options' => $this->cateOptions,
        ];
    }

    /**
     * @rbac ({title:'编辑文章'})
     * @throws \Exception
     */
    public function editAction()
    {
        $id = $this->getRequestInt('id');
        $this->model = CmsArticle::mustFindFirst($id);

        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            $this->save($data);
            return $this->saveModelResponse(true, false);
        }

        $row = $this->model->toArray();
        $row['images'] = $this->vv->uploadfileService()->getImages($this->model->image_ids);
        $row['content'] = $this->cms->contentService()->getContentById($this->model->content_id);

        return [
            'options' => $this->cateOptions,
            'row' => $row,
        ];
    }

    protected function save($data): bool
    {
        $keys = ['cate_id', 'cover', 'title', 'keywords', 'summary', 'author', 'hits', 'image_ids'];
        $this->model->assign($data, $keys);

        Transaction::db($this->vv->db(), function () use ($data) {
            if (isset($data['content']) || $this->model->content_id > 0) {
                $cc1 = $this->cms->contentService()->saveContentDataById($this->model->content_id, $data['content']);
                $this->model->content_id = $cc1->id;
            }

            if (!$this->model->save()) {
                throw new \Exception('添加文章失败:' . $this->model->getFirstError());
            }
        });
        return true;
    }

    /**
     * @rbac ({title:'文章审核'})
     */
    public function cstatusAction()
    {
        $this->mustPostMethod();
        $data = $this->request->getPost();
        $this->vv->validate()->check($data, [
            'id' => 'required|int',
            'cstatus' => 'in:' . join(',', array_keys(CmsArticle::mapCheckStatus())),
        ]);
        if ($data['cstatus'] == CmsArticle::CheckStatusDeny) {
            if (empty($data['cmessage'])) {
                return $this->error('请填写不通过的原因');
            }
        }
        $this->model = CmsArticle::mustFindFirst($data['id']);
        $this->model->cstatus = $data['cstatus'];
        $this->model->cmessage = $data['cmessage'];
        $this->model->cuser_id = $this->loginUser()->id;
        return $this->saveModelResponse($this->model->save(), false);
    }

    /**
     * @rbac ({title:'文章预览'})
     * @throws \Exception
     */
    public function previewAction()
    {
        $id = $this->getRequestInt('id');
        $this->model = CmsArticle::mustFindFirst($id);
        $row = $this->model->toArray();
        $row['images'] = $this->vv->uploadfileService()->getImages($this->model->image_ids);
        $row['content'] = $this->cms->contentService()->getContentById($this->model->content_id);
        return $row;
    }
}