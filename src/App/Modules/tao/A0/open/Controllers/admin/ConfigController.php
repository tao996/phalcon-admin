<?php

namespace App\Modules\tao\A0\open\Controllers\admin;

use App\Modules\tao\A0\open\BaseOpenController;
use App\Modules\tao\A0\open\Models\OpenConfig;


/**
 * @rbac ({title:'开放平台配置'})
 * @property OpenConfig $model
 */
class ConfigController extends BaseOpenController
{

    protected function localInitialize(): void
    {
        $this->model = new OpenConfig();
    }

    /**
     * @rbac ({title:'公共配置'})
     */
    public function indexAction()
    {
        $rows = $this->mvc->configService()->cache();
        // 更新配置信息
        if ($this->request->isPost()) {
            $hasChange = false;
            foreach ($this->request->getPost() as $key => $value) {
                if (key_exists($key, $rows) && $rows[$key] != $value) {
                    $this->mvc->configService()->updateValue($this->model, $key, $value);
                    $hasChange = true;
                }
            }
            if ($hasChange) {
                $this->mvc->configService()->cache();
            }
            return $this->success('更新开放平台配置成功');
        }

        return $rows;
    }

    /**
     * @throws \Exception
     */
    protected function afterModelChange(string $action): void
    {
        $this->mvc->configService()->cache();
    }
}