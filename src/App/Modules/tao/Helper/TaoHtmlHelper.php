<?php

namespace App\Modules\tao\Helper;

use App\Modules\tao\Helper\Layui\LayuiForm;
use App\Modules\tao\Helper\Layui\LayuiHtml;
use App\Modules\tao\Helper\Layui\LayuiSearch;
use App\Modules\tao\views\assets\layui\AssetsLayui;
use Phax\Foundation\AppService;
use Phax\Helper\HtmlHelper;

/**
 * src/App/Modules/tao/sdk 目录路径
 */
const PATH_MODULE_TAO_SDK = PATH_APP_MODULES . 'tao' . DIRECTORY_SEPARATOR . 'sdk';
/**
 * 静态资源 src/App/Modules/tao/views/assets 目录路径
 */
const PATH_MODULE_TAO_ASSETS = PATH_APP_MODULES . 'tao' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;

const MSTATIC_TAO_ASSETS = '/mstatic/tao/assets/';

class TaoHtmlHelper extends HtmlHelper
{
    public string $mainLayoutView = PATH_APP_MODULES . 'tao/views/layui/index';

    /**
     * @var array|string 面包屑导航
     */
    public array|string $breadcrumb = '';

    public function layui(): AssetsLayui
    {
        return AppService::getShared('tao.layui');
    }

    public function layuiHtml(): LayuiHtml
    {
        return AppService::getShared('tao.layuiHtml');
    }

    public function layuiForm(): LayuiForm
    {
        return AppService::getShared('tao.layuiForm');
    }

    public function layuiSearch(): LayuiSearch
    {
        return AppService::getShared('tao.layuiSearch');
    }

    public function doneViewResponse(): void
    {
        parent::doneViewResponse();
        if ($this->breadcrumb) {
            $this->layuiHtml()->addBreadcrumbItem($this->breadcrumb);
        }
    }
}