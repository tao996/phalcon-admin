<?php

namespace App\Modules\tao\Controllers\admin;

use App\Modules\tao\BaseController;
use App\Modules\tao\Helper\Libs\RBAC;
use App\Modules\tao\Models\SystemConfig;
use App\Modules\tao\Services\ConfigService;
use App\Modules\tao\Services\LogService;

#[RBAC(title: '配置管理')]
class ConfigController extends BaseController
{
    protected string $htmlTitle = '系统配置';

    public array $enableActions = [
        'index',
        'save',
        'reload'
    ];

    protected function afterInitialize(): void
    {
        $this->model = new SystemConfig();
    }

    /**
     * @param string $gname 配置组名称
     */
    #[RBAC(title: '保存配置')]
    public function saveAction(string $gname): array
    {
        $this->mustPostMethod();
        $gnames = ConfigService::findGname();

        if (!in_array($gname, $gnames)) {
            return $this->error('不允许修改的群组属性');
        }
        $configRows = ConfigService::groupRows($gname); // 全部配置信息
        $model = SystemConfig::getObject();
        // 有提交值的才修改
        $hasChange = false;
        foreach ($this->request->getPost() as $key => $value) {
            if (key_exists($key, $configRows) && $configRows[$key] != $value) {
                $model->updateValue($gname, $key, $value);
                $hasChange = true;
            }
        }
        if ($hasChange) {
            ConfigService::forceCache();
            LogService::insert($model->tableTitle(), '修改配置');
        }

        return $this->success('更新成功');
    }

    #[RBAC(title: '重载缓存')]
    public function reloadAction(): array
    {
        ConfigService::forceCache();
        return $this->success('更新配置成功');
    }
}