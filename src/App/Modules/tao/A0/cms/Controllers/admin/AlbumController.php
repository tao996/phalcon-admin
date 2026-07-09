<?php

namespace App\Modules\tao\A0\cms\Controllers\admin;

use App\Modules\tao\A0\cms\Models\CmsAlbum;
use App\Modules\tao\BaseController;
use App\Modules\tao\Helper\Libs\RBAC;
use App\Modules\tao\Services\UploadfileService;

/**
 * @property CmsAlbum $model
 */
#[RBAC(title: '图集管理')]
class AlbumController extends BaseController
{
    protected string $htmlTitle = '图集';
    protected array $appendModifyFields = ['tag'];

    public function afterInitialize(): void
    {
        $this->model = new CmsAlbum();
    }

    #[RBAC(title:'修改图集')]
    public function editAction()
    {

        if ($this->request->isPost()){
            return parent::editAction();
        }

        $id = $this->getRequestInt('id');
        $this->model = CmsAlbum::mustFindFirst($id);
        $row = $this->model->toArray();
        $row['images'] = UploadfileService::getImages($this->model->image_ids);
        return $row;
    }

    #[RBAC(title:'图集预览')]
    public function previewAction()
    {
        return $this->editAction();
    }
}