<?php

namespace App\Modules\tao\A0\cms\Controllers\admin;


use App\Modules\tao\A0\cms\BaseTaoA0CmsController;
use App\Modules\tao\A0\cms\Models\CmsContent;
use App\Modules\tao\A0\cms\Models\CmsPage;
use Phax\Db\QueryBuilder;
use Phax\Db\Transaction;
use Phax\Utils\MyData;

/**
 * @rbac ({title:'单页管理'})
 * @property CmsPage $model
 */
class PageController extends BaseTaoA0CmsController
{
    public function afterInitialize(): void
    {
        parent::afterInitialize();
        $this->model = new CmsPage();
    }

    protected string $htmlName = '单页';
    protected string|array $indexQueryColumns = ['id', 'tag', 'name', 'title', 'sort', 'status'];
    protected array $allowModifyFields = ['sort', 'status'];

    protected function indexActionQueryBuilder(QueryBuilder $queryBuilder): void
    {
        $queryBuilder->int('status', $this->request->getQuery('status', 'int', 0));
        $queryBuilder->int('tag', $this->request->getQuery('tag'));
    }

    /**
     * @rbac ({title:'添加单页'})
     */
    public function addAction()
    {
        if ($this->request->isPost()) {
            $data = $this->request->getPost();

            $keys = ['tag', 'sort', 'title', 'name', 'content'];
            MyData::mustHasSet($data, $keys, ['sort', 'tag']);

            $this->model->assign($data, $keys);

            if ($this->model->isRepeat()) {
                return $this->error('重复的 tag+name');
            }


            Transaction::db($this->vv->db(), function () use ($data) {
                $cc = new CmsContent();
                $cc->content = $data['content'];
                if ($cc->create()) {
                    $this->model->content_id = $cc->id;
                    if ($this->model->create() === false) {
                        throw new \Exception('save page failed:' . $this->model->getFirstError());
                    }
                } else {
                    throw new \Exception('save content failed:' . $cc->getFirstError());
                }
            });

            return $this->saveModelResponse(true, true);
        }
        return [];
    }

    /**
     * @rbac ({title:'编辑单页'})
     * @throws \Exception
     */
    public function editAction()
    {
        $id = $this->getRequestQueryInt('id');
        if (!$this->model = CmsPage::findFirst($id)) {
            return $this->error('没有找到指定记录');
        }
        if ($this->request->isPost()) {
            $data = $this->request->getPost();

            $keys = ['tag', 'sort', 'title', 'name', 'content'];
            MyData::mustHasSet($data, $keys, ['sort', 'tag']);
            $this->model->assign($data, ['tag', 'sort', 'title', 'name']);

            $cc = $this->cms->contentService()->getById($this->model->content_id) ?: new CmsContent();
            $cc->content = $data['content'];

            Transaction::db($this->vv->db(), function () use ($cc) {
                if (!$cc->save()) {
                    throw new \Exception('save content failed:' . $cc->getFirstError());
                }
                $this->model->content_id = $cc->id;
                if (!$this->model->save()) {
                    throw new \Exception('save page failed:' . $this->model->getFirstError());
                }
            });
            return $this->saveModelResponse(true, false);
        }

        $data = $this->model->toArray();
        $data['content'] = $this->cms->contentService()->getContentById($this->model->content_id);
        return $data;
    }
}