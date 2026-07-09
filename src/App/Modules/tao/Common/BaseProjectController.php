<?php

namespace App\Modules\tao\Common;

use App\Modules\tao\sdk\phaxui\HtmlAssets;

/**
 * 项目前端控制器
 * ```
 * projectDir/
 *  |-- views
 *      |-- layui/ # the default theme
 *          |-- index.phtml
 * ```
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