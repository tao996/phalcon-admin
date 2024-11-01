<?php

namespace App\Modules\tao\Controllers\user;

use App\Modules\tao\BaseController;
use App\Modules\tao\Models\SystemQuick;
use Phax\Mvc\Model;

/**
 * @property SystemQuick $model
 */
class QuickController extends BaseController
{
    protected string $htmlName = '链接';

    protected array|string $userActions = '*';

    protected array $sort = [
        'sort' => 'desc',
        'id' => 'desc',
    ];

    protected array $saveWhiteList = [
        'href',
        'title',
        'icon',
        'sort',
        'remark'
    ];

    public function afterInitialize(): void
    {
        $this->model = new SystemQuick();
    }

    protected array $allowModifyFields = [
        'sort',
        'title',
        'status',
        'href',
        'remark',
    ];

    /**
     * @throws \Exception
     */
    public function addAction()
    {
        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            $this->model->assign($this->beforeModelSaveAssign($data), $this->saveWhiteList);
            $this->model->user_id = $this->loginUser()->id;

            return $this->saveModelResponse($this->model->create());
        }
        return [];
    }

    protected function beforeModelSaveAssign(array $data): array
    {
        $this->vv->validate()->check($data, [
            'href|链接地址' => 'require',
            'title|快捷名称' => 'require',
        ]);
        return $data;
    }

    /**
     * @throws \Exception
     */
    public function editAction()
    {
        $id = $this->getRequestQueryInt('id');

        $this->model = SystemQuick::findFirst(['id' => $id]);
        $this->checkModelActionAccess($this->model);

        if ($this->request->isPost()) {
            $this->model->assign($this->beforeModelSaveAssign($this->request->getPost()), $this->saveWhiteList);
            return $this->saveModelResponse($this->model->save(), false);
        }
        return $this->model->toArray();
    }
}