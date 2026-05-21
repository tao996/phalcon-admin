<?php

namespace App\Modules\tao\Services;

use App\Modules\tao\Config\Data;
use App\Modules\tao\Helper\MyMvcHelper;
use App\Modules\tao\Models\SystemMenu;
use App\Modules\tao\Models\SystemNode;
use Phax\Support\Router;

class MenuService
{
    private string $work_prefix = '';

    public function __construct(public MyMvcHelper $mvc)
    {
        if ($this->mvc->route()->urlOptions[Router::$cliKeyword]) {
            $this->work_prefix = '/' . Router::$cliKeyword;
        }
    }

    public function href($href, $type = 0, string|array $params = []): string
    {
        if ($href) {
            if (SystemNode::KIND_MODULE == $type) {
                if ($this->work_prefix && str_starts_with($href,$this->work_prefix)){
                    return $href;
                }
                if (str_starts_with($href, Router::$modulePrefix)) {
                    return $href;
                }
                return $this->work_prefix . Router::$modulePrefix . ltrim($href, '/');
            } elseif (SystemNode::KIND_PROJECT == $type) {
                if (str_starts_with($href, Router::$projectPrefix)) {
                    return $href;
                }
                return $this->work_prefix . Router::$projectPrefix . ltrim($href, '/');
            }
        }
        return $href;
    }

    public function homeId(): int
    {
        static $homeId = null;
        if (is_null($homeId)) {
            $homeId = SystemMenu::queryBuilder($this->mvc->getDi())
                ->int('pid', Data::HOME_PID)
                ->value('id');
        }
        return $homeId;
    }
}