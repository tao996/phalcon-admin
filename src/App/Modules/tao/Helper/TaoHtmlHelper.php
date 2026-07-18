<?php

namespace App\Modules\tao\Helper;

use App\Modules\tao\sdk\phaxui\Layui\Layui;
use App\Modules\tao\sdk\phaxui\Layui\LayuiForm;
use App\Modules\tao\sdk\phaxui\Layui\LayuiHtml;
use App\Modules\tao\sdk\phaxui\Layui\LayuiSearch;
use Phax\Foundation\AppService;
use Phax\Helper\HtmlHelper;

class TaoHtmlHelper extends HtmlHelper
{

    public function __construct()
    {
        parent::__construct();
        $this->beforeOutputHeaders = [
            function () {
                /**
                 * @var Layui $layui
                 */
                $layui = AppService::getShared('tao.layui');
                $layui->header();
            }
        ];
    }

    public function layui(): Layui
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
}