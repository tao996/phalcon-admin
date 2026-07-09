<?php

namespace App\Modules\tao\A0\cms\Controllers\admin;


use App\Modules\tao\A0\cms\Models\CmsContent;
use App\Modules\tao\A0\cms\Models\CmsPage;
use App\Modules\tao\A0\cms\Services\CmsContentService;
use App\Modules\tao\A0\cms\Services\CmsPageService;
use App\Modules\tao\BaseController;
use App\Modules\tao\Helper\Libs\RBAC;
use Phax\Db\QueryBuilder;
use Phax\Db\Transaction;
use Phax\Support\Exception\BusinessException;
use Phax\Utils\MyData;

/**
 * @property CmsPage $model
 */
#[RBAC(title: '单页管理')]
class PageController extends BaseController
{
    public function afterInitialize(): void
    {
        $this->model = new CmsPage();
    }

    protected string $htmlTitle = '单页';
    protected string|array $modelQueryColumns = ['id', 'tag', 'name', 'title', 'sort', 'status'];
    protected array $allowModifyFields = ['sort', 'status'];

    protected function actionQuery(QueryBuilder $queryBuilder): void
    {
        parent::actionQuery($queryBuilder);
        $queryBuilder->int('tag', $this->request->getQuery('tag'));
    }

    #[RBAC(title: '添加单页')]
    public function addAction()
    {
        if ($this->request->isPost()) {
            $data = $this->request->getPost();

            $keys = ['tag', 'sort', 'title', 'name', 'content'];
            MyData::mustHasSet($data, $keys, ['sort', 'tag']);

            $this->model->assign($data, $keys);

            if (CmsPageService::isRepeat($this->model)) {
                return $this->error('重复的 tag+name');
            }


            Transaction::db(function () use ($data) {
                $cc = new CmsContent();
                $cc->content = $data['content'];
                if ($cc->create()) {
                    $this->model->content_id = $cc->id;
                    if ($this->model->create() === false) {
                        throw new BusinessException('文章失败:' . $this->model->getFirstError());
                    }
                } else {
                    throw new BusinessException('文章内容保存失败:' . $cc->getFirstError());
                }
            });

            return $this->saveModelResponse(true, 'add');
        }
        return [];
    }

    #[RBAC(title: '编辑单页')]
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

            $cc = CmsContentService::getById($this->model->content_id) ?: new CmsContent();
            $cc->content = $data['content'];

            Transaction::db(function () use ($cc) {
                if (!$cc->save()) {
                    throw new BusinessException('save content failed:' . $cc->getFirstError());
                }
                $this->model->content_id = $cc->id;
                if (!$this->model->save()) {
                    throw new BusinessException('save page failed:' . $this->model->getFirstError());
                }
            });
            return $this->saveModelResponse(true);
        }

        $data = $this->model->toArray();
        $data['content'] = CmsContentService::getContentById($this->model->content_id);
        return $data;
    }
}