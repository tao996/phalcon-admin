<?php

namespace App\Modules\tao\A0\open\Controllers\admin;


use App\Modules\tao\A0\open\BaseOpenController;
use App\Modules\tao\A0\open\Models\OpenApp;
use App\Modules\tao\Helper\Libs\RBAC;
use Phax\Support\Exception\BusinessException;
use Phax\Utils\MyData;

/**
 * @property OpenApp $model
 */
#[RBAC(title: '开放平台应用管理')]
class AppController extends BaseOpenController
{
    protected string $htmlTitle = '应用';

    protected string|array $modelHiddenColumns = ['secret'];
    protected string $modelOrderBy = 'sort desc,id desc';
    protected array $allowModifyFields = ['status', 'sort', 'online', 'sandbox'];

    public function localInitialize(): void
    {
        $this->model = new OpenApp();
    }

    protected function buildIndexResult(int $count, \Phax\Db\QueryBuilder $queryBuilder): array
    {
        $rows = parent::buildIndexResult($count, $queryBuilder);
        foreach ($rows as $index => $row) {
            $rows[$index]['public_key'] = !empty($row['public_key']);
            $rows[$index]['rsa_public_key'] = !empty($row['rsa_public_key']);
            $rows[$index]['rsa_private_key'] = !empty($row['rsa_private_key']);
        }
        return $rows;
    }

    protected function beforeModelAssign($data): array
    {
        $this->vv->validate()->check($data, [
            'appid' => 'required',
            'platform|平台' => 'required',
            'title|应用名称' => 'required',
            'kind|应用类型' => 'required',
            'secret' => 'required'
        ]);
        $data['sandbox'] = (int)MyData::isTrueWith($data, 'sandbox');
        return $data;
    }

    protected function beforeModelSave(): void
    {
        if ($this->model->getQueryBuilder($this->getDI())
            ->where('appid', $this->model->appid)
            ->notEqual('id', $this->model->id)->exits()
        ) {
            throw new BusinessException('重复的 appid');
        }
    }

    #[RBAC(title: '修改证书')]
    public function certAction()
    {
        $this->mustPostMethod();
        $data = $this->request->getPost();

        $this->vv->validate()->check($data, [
            'id' => 'required|int',
            'name' => 'required|in:public_key,rsa_public_key,rsa_private_key',
        ]);
        $this->model = OpenApp::mustFindFirst($data['id']);
        $pIndexName = $this->helper->appService()->getPIndex($data['name']);

        // 清除证书
        if (isset($data['value']) && empty($data['value'])) {
            $this->model->assign([
                $data['name'] => '',
                $pIndexName => 0
            ]);
            return $this->model->save()
                ? $this->success('清除证书成功')
                : $this->error($this->model->getFirstError());
        }

        // 上传证书
        if ($this->request->hasFiles()) {
            $f = $this->request->getUploadedFiles()[0];
            $v = file_get_contents($f->getTempName());
        } else { // 输入证书
            $v = MyData::getString($data, 'value');
        }

        if ($this->helper->appService()->encrypt($this->model, $data['name'], $v)) {
            $this->helper->appService()->cache();
            return $this->success('保存证书成功');
        } else {
            return $this->error($this->model->getFirstError());
        }
    }

    /**
     * @throws \Exception
     */
    protected function afterModelChange(string $action): void
    {
        $this->helper->appService()->cache();
    }

}