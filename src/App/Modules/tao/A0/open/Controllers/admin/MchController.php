<?php

namespace App\Modules\tao\A0\open\Controllers\admin;

use App\Modules\tao\A0\open\BaseOpenController;
use App\Modules\tao\A0\open\Helper\Libs\PayCertHelper;
use App\Modules\tao\A0\open\Models\OpenMch;
use App\Modules\tao\Helper\Libs\RBAC;
use Phax\Utils\MyData;

/**
 * @property OpenMch $model
 */
#[RBAC(title: '商户应用')]
class MchController extends BaseOpenController
{
    protected string|array $modelHiddenColumns = ['secret_key'];
    protected array|string $superAdminActions = '*';
    protected string $htmlTitle = '商户';

    public function localInitialize(): void
    {
        $this->model = new OpenMch();
    }

    protected function buildIndexResult(int $count, \Phax\Db\QueryBuilder $queryBuilder): array
    {
        $rows = parent::buildIndexResult($count, $queryBuilder);
        foreach ($rows as $index => $row) {
            $rows[$index]['private_key'] = !empty($row['private_key']);
            $rows[$index]['pubkey'] = !empty($row['pubkey']);
            $rows[$index]['certificate'] = !empty($row['certificate']);
        }
        return $rows;
    }

    protected function beforeModelAssign($data): array
    {
        $this->vv->validate()->check($data, [
            'mchid|商户号ID' => 'required',
            'secret_key|V3 api 秘钥' => 'required',
            'pubkey_id|微信支付公钥' => 'required'
        ]);
        return $data;
    }

    /**
     * 2024 年 Q3，微信支付官方开启了「微信支付公钥」平替「平台证书」方案
     * @throws \Exception
     */
    #[RBAC(title: '上传证书')]
    public function certAction()
    {
        $this->mustPostMethod();
        $data = $this->request->getPost();
        MyData::mustHasSet($data, ['id', 'name']);
        if (!in_array($data['name'], ['private_key', 'certificate', 'pubkey', 'platform_cert'])) {
            return $this->error('不支持上传的证书类型');
        }

        $this->model = OpenMch::mustFindFirst($data['id']);
        if ($this->request->hasFiles()) {
            $f = $this->request->getUploadedFiles()[0];
            $saveName = md5_file($f->getTempName());
            $dir = PayCertHelper::dir();

            if ($f->moveTo($dir . $saveName)) {
                $this->model->assign([
                    $data['name'] => $saveName
                ]);
            } else {
                return $this->error('保存上传证书失败');
            }
        } else {
            $this->model->assign([$data['name'] => '']);
        }
        return $this->saveModelResponse($this->model->save());
    }

    protected function afterModelChange(string $action): void
    {
        $this->helper->mchService()->cache();
    }
}