<?php
declare(strict_types=1);

namespace App\Modules\tao\Helper;


use App\Modules\tao\Config\Data;

class TaoHtmlHelper
{
    /**
     * 常用的状态
     * @param MyMvcHelper $vv
     * @return string
     */
    static public function status(\App\Modules\tao\Helper\MyMvcHelper $vv): string
    {
        return $vv->layuiForm()->select('状态', 'status', vtOptions: Data::MAP_STATUS,
            value: $vv->pick('status',1));
    }

    /**
     * @return string 表单提交/重置按钮
     */
    static public function formSubmit(): string
    {
        return '<div class="hr-line"></div>
    <div class="layui-form-item text-center">
        <button type="submit" class="layui-btn layui-btn-normal layui-btn-sm" lay-submit>确认</button>
        <button type="reset" class="layui-btn layui-btn-primary layui-btn-sm">重置</button>
    </div>';
    }

    static public function captcha()
    {

    }
}