<?php

namespace App\Modules\tao\A0\cms\Controllers\user;

use App\Modules\tao\A0\cms\BaseTaoA0CmsController;
use App\Modules\tao\Models\SystemUploadfile;
use Phax\Utils\MyData;

class HelperController extends BaseTaoA0CmsController
{
    protected array|string $userActions = '*';
    public array $enableActions = ['select', 'edit'];

    /**
     * @rbac ({title:'图集图片选择'})
     */
    public function selectAction()
    {
        return [];
    }

    /**
     * @rbac ({title:'图集图片修改'})
     * @throws \Exception
     */
    public function editAction()
    {
        $id = $this->getRequestQueryInt('id');
        $this->model = SystemUploadfile::mustFindFirst('id=' . $id . ' AND user_id=' . $this->loginUser()->id);
        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            MyData::mustHasSet($data, ['summary']);
            $this->model->assign($data, ['summary']);
            return $this->saveModelResponse($this->model->save(), false);
        }
        return $this->model->toArray();
    }
}