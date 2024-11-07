<?php

namespace App\Modules\tao\A0\cms\Controllers\admin;

use App\Modules\tao\A0\cms\BaseTaoA0CmsController;
use App\Modules\tao\A0\cms\Models\CmsAlbum;

/**
 * @property CmsAlbum $model
 * @rbac ({title:'图集管理'})
 */
class AlbumController extends BaseTaoA0CmsController
{
    protected string $htmlTitle = '图集';
    protected array $appendModifyFields = ['tag'];

    public function afterInitialize(): void
    {
        parent::afterInitialize();
        $this->model = new CmsAlbum();
    }

    protected array $saveWhiteList = [
        'cover', 'title', 'tag', 'summary','image_ids'
    ];

    /**
     * @rbac ({title:'修改图集'})
     * @throws \Exception
     */
    public function editAction()
    {

        if ($this->request->isPost()){
            return parent::editAction();
        }

        $id = $this->getRequestInt('id');
        $this->model = CmsAlbum::mustFindFirst($id);
        $row = $this->model->toArray();
        $row['images'] = $this->vv->uploadfileService()->getImages($this->model->image_ids);
        return $row;
    }

    /**
     * @rbac ({title:'图集预览'})
     */
    public function previewAction()
    {
        return $this->editAction();
    }
}