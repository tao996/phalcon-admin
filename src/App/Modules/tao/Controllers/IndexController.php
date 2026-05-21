<?php

namespace App\Modules\tao\Controllers;

use App\Modules\tao\BaseController;

/**
 * 后台框架
 */
class IndexController extends BaseController
{
    public bool $disableUpdateActions = true;
    protected array|string $userActions = '*';

    public function indexAction(): array
    {
        $this->disabledMainLayout = true;
        $data = [
            'menuTree' => $this->vv->loginUserHelper()->getMenuTree()
        ];
        return $this->isApiRequest() ? $this->success('', $data) : $data;
    }

    /**
     * 后台首页：欢迎界面
     * @return array
     */
    public function welcomeAction()
    {
        return [];
    }
}