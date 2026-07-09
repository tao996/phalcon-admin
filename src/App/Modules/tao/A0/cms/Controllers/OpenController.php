<?php

namespace App\Modules\tao\A0\cms\Controllers;

use App\Modules\tao\A0\cms\Services\CmsPageService;
use App\Modules\tao\BaseController;
use Phax\Foundation\AppService;
use Phax\Support\Exception\BusinessException;


class OpenController extends BaseController
{
    protected array|string $openActions = ['page', 'terms'];
    protected bool $disabledMainLayout = true;

    /**
     * 单页信息显示
     * @link http://localhost:8071/m/tao.cms/open/page/terms
     * @return array
     */
    public function pageAction(string $name)
    {
        if (empty($name)) {
            throw new BusinessException('page name is empty');
        }
        $tag = $this->request->getQuery('tag', 'string', AppService::route()->getProject());
        $page = CmsPageService::findFirst($tag, $name);
        if (empty($page)) {
            throw new BusinessException('page not found for ' . $name);
        }
        return $page;
    }
}