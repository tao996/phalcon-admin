<?php

namespace App\Modules\tao\Controllers\user;

use App\Modules\tao\BaseController;

use App\Modules\tao\Helper\FileUploadHelper;
use App\Modules\tao\Models\SystemUploadfile;

class FileController extends BaseController
{
    protected array|string $userActions = '*';

    protected array $allowModifyFields = ['summary'];

    public function afterInitialize(): void
    {
        $this->model = new SystemUploadfile();
    }

    /**
     * 文件列表
     * @throws \Exception
     */
    public function indexAction(): array
    {
        if ($this->isApiRequest()) {
            $model = new SystemUploadfile();
            $b = $model->getQueryBuilder()
                ->like('summary', $this->request->get('keyword', 'string', ''))
                ->int('status', $this->request->get('status', 'int', 0))
                ->int('user_id', $this->loginUser()->id);
            $count = $b->count();
            $rows = $this->pagination($b)->columns('id,url,summary,created_at,width,height')
                ->orderBy('id desc')
                ->find();
            return $this->successPagination($count, $rows);
        }
        $this->disabledMainLayout = true;
        return [];
    }

    /**
     * 添加图片
     * @throws \Exception
     */
    public function saveAction(): array
    {
        $fp = new FileUploadHelper($this->vv);
        $sf = $fp->fromRequest()->validate()->save();
        $sf->user_id = $this->loginUser()->id;
        if ($sf->save()) {
            return $this->success('上传成功', [
                'id' => $sf->id,
                'url' => $sf->url
            ]);
        } else {
            return $this->error($sf->getErrors());
        }
    }

    /**
     * 通过编辑器上传图片
     * @throws \Exception
     */
    public function editorAction()
    {
        $fp = new FileUploadHelper($this->vv);
        $sf = $fp->fromRequest()->validate()->save();
        $sf->user_id = $this->loginUser()->id;
        if ($sf->save()) {
            return $this->json([
                'error' => ['message' => '上传成功', 'number' => 201],
                'filename' => '',
                'uploaded' => 1,
                'url' => $sf->url,
            ]);
        } else {
            return $this->error($sf->getErrors());
        }
    }
}