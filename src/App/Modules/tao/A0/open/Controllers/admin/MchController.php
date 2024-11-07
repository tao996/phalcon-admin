<?php

namespace App\Modules\tao\A0\open\Controllers\admin;

use App\Modules\tao\A0\open\BaseOpenController;
use App\Modules\tao\A0\open\Helper\Libs\PayCertHelper;
use App\Modules\tao\A0\open\Models\OpenMch;
use Phax\Utils\MyData;

/**
 * @rbac ({title:'商户应用'})
 * @property OpenMch $model
 */
class MchController extends BaseOpenController
{
    protected string|array $indexHiddenColumns = ['secret_key'];
    protected array|string $superAdminActions = '*';
    protected string $htmlTitle = '商户';

    public function localInitialize(): void
    {
        $this->model = new OpenMch();
    }

    protected function indexActionGetResult(int $count, \Phax\Db\QueryBuilder $queryBuilder): array
    {
        $rows = parent::indexActionGetResult($count, $queryBuilder);
        foreach ($rows as $index => $row) {
            $rows[$index]['private_key'] = !empty($row['private_key']);
            $rows[$index]['platform_cert'] = !empty($row['platform_cert']);
            $rows[$index]['certificate'] = !empty($row['certificate']);
        }
        return $rows;
    }

    protected function beforeModelSaveAssign($data): array
    {
        $this->vv->validate()->check($data, [
            'mchid|商户号ID' => 'required',
            'secret_key|V3 api 秘钥' => 'required',
        ]);
        return $data;
    }

    /**
     * @rbac ({title:'上传证书'})
     * @throws \Exception
     */
    public function certAction()
    {
        $this->mustPostMethod();
        $data = $this->request->getPost();
        MyData::mustHasSet($data, ['id', 'name']);
        if (!in_array($data['name'], ['private_key', 'certificate', 'platform_cert'])) {
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
        return $this->saveModelResponse($this->model->save(), false);
    }
}