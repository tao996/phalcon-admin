<?php

namespace App\Modules\tao\Helper;

use App\Modules\tao\sdk\phaxui\Layui\Layui;
use App\Modules\tao\sdk\phaxui\Layui\LayuiForm;
use App\Modules\tao\sdk\phaxui\Layui\LayuiFormSearch;
use App\Modules\tao\sdk\phaxui\Layui\LayuiHtml;
use App\Modules\tao\sdk\phaxui\TaoHtmlHelper;
use Phax\Foundation\AppService;
use Phax\Helper\HtmlHelper;
use Phax\Helper\MyMvc;

class MyMvcHelper extends MyMvc
{
    /**
     * @var string 指定要加载的脚本
     */
    public string $pickName = '';

    /**
     * 在统一入口 BaseResponseController 中被初始化
     * @param \Phalcon\Di\Di $di
     */
    public function __construct(\Phalcon\Di\Di $di)
    {
        parent::__construct($di);

        $mvc = $this;
        $this->di->setShared('layui', function () use ($mvc) {
            /**
             * @var $html TaoHtmlHelper
             */
            $html = $mvc->html();
            return new Layui($html);
        });

        $this->di->setShared('tao.layuiHtml', function () use ($mvc) {
            return new LayuiHtml();
        });
        $this->di->setShared('tao.layuiForm', function () use ($mvc) {
            return new LayuiForm($mvc);
        });
        $this->di->setShared('tao.layuiFormSearch', function () use ($mvc) {
            return new LayuiFormSearch($mvc);
        });
    }

    public function html(): HtmlHelper
    {
        static $html = null;
        if (is_null($html)) {
            $html = new TaoHtmlHelper($this);
        }
        return $html;
    }

    /**
     * 添加当前视图目录下的文件
     * @param $file string 待添加文件名称，如 tao.css
     * @return bool
     */
    public function addViewFile(string $file): bool
    {
        $pathFile = $this->di->get('view')->getViewsDir() . $file;
        return $this->html()->includeAssetsFile($pathFile);
    }

    /**
     * 如果当前模板下存在着同名 js 文件，则引入它；比如你的模板为 add.phtml，如果存在 add.js 则会引入它
     * @return bool
     */
    public function appendTemplateJs(): bool
    {
        $theme = AppService::route()->theme;
        $pickName = $this->pickName ?: AppService::route()->getPickView(true);
        $jsFile = join(
                '/',
                $theme
                    ? [AppService::route()->getViewDIR(), $theme, $pickName]
                    : [AppService::route()->getViewDIR(), $pickName]
            ) . '.js';
        return $this->html()->includeAssetsFile($jsFile, 'js');
    }


    public function layui(): Layui
    {
        return $this->di->getShared('layui');
    }

    public function layuiHtml(): LayuiHtml
    {
        return $this->di->getShared('tao.layuiHtml');
    }

    /**
     * 编辑页面快速生成表单组件
     * @return LayuiForm
     */
    public function layuiForm(): LayuiForm
    {
        return $this->di->getShared('tao.layuiForm');
    }

    /**
     * 首页，快速生成搜索表单组件
     * @return LayuiFormSearch
     */
    public function layuiFormSearch(): LayuiFormSearch
    {
        return $this->di->getShared('tao.layuiFormSearch');
    }

}