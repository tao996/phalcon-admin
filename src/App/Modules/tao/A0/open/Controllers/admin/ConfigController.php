<?php

namespace App\Modules\tao\A0\open\Controllers\admin;

use App\Modules\tao\A0\open\Models\OpenConfig;
use App\Modules\tao\A0\open\Service\OpenConfigService;
use App\Modules\tao\BaseController;
use App\Modules\tao\Helper\Libs\RBAC;


/**
 * @property OpenConfig $model
 */
#[RBAC(title: '开放平台配置')]
class ConfigController extends BaseController
{

    protected function afterInitialize(): void
    {
        $this->model = new OpenConfig();
    }

    #[RBAC(title: '公共配置')]
    public function indexAction()
    {
        $rows = OpenConfigService::findCache();
        // 更新配置信息
        if ($this->request->isPost()) {
            $hasChange = false;
            foreach ($this->request->getPost() as $key => $value) {
                if (key_exists($key, $rows) && $rows[$key] != $value) {
                    OpenConfigService::updateValue($this->model, $key, $value);
                    $hasChange = true;
                }
            }
            if ($hasChange) {
                OpenConfigService::findCache();
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
        OpenConfigService::findCache();
    }
}