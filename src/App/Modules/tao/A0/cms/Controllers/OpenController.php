<?php

namespace App\Modules\tao\A0\cms\Controllers;

use App\Modules\tao\A0\cms\BaseTaoA0CmsController;


class OpenController extends BaseTaoA0CmsController
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
            throw new \Exception('page name is empty');
        }
        $tag = $this->request->getQuery('tag', 'string', $this->vv->route()->getProject());
        $page = $this->helper->pageService()->findFirst($tag, $name);
        if (empty($page)) {
            throw new \Exception('page not found for ' . $name);
        }
        return $page;
    }
}