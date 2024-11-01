<?php

namespace App\Modules\tao\Common;


use App\Modules\tao\A0\cms\Helper\MyCmsMvcHelper;
use App\Modules\tao\A0\open\Helper\MyOpenMvcHelper;
use App\Modules\tao\sdk\phaxui\HtmlAssets;

/**
 * 项目前端控制器
 */
class BaseProjectController extends \App\Modules\tao\BaseController
{
    /**
     * @var bool 是否有控制台（后台管理控制器）
     */
    protected bool $console = false;
    /**
     * @var string 默认前端 Layout 模板（通常包含了导航/页头/页脚）
     */
    protected string $layoutView = 'base';

    /**
     * @var array|string 面包屑导航
     */
    protected array|string $breadcrumb = '';

    public function cmsHelper(): MyCmsMvcHelper
    {
        return $this->vv->a0cmsHelper();
    }

    public function openHelper(): MyOpenMvcHelper
    {
        return $this->vv->a0openHelper();
    }

    protected function beforeViewResponse(mixed $data)
    {
        HtmlAssets::initWithCdn();
        if ($this->breadcrumb) {
            $this->vv->layuiHtml()->addBreadcrumbItem($this->breadcrumb);
        }
        // 控制臺不需要
        $this->view->setVars([
            'console' => $this->console,
        ]);
        if (!$this->console && $this->layoutView) {
            $this->view->setLayout($this->layoutView);
        }

        return parent::beforeViewResponse($data);
    }
}