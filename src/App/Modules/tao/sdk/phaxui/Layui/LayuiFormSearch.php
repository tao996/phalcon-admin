<?php

namespace App\Modules\tao\sdk\phaxui\Layui;

use App\Modules\tao\Config\Data;
use App\Modules\tao\Helper\MyMvcHelper;

class LayuiFormSearch
{
    public function __construct(protected MyMvcHelper $mvc)
    {
    }

    public function status(): string
    {
        // 只是为了有一个引用
        $enable = Data::STATUS_NORMAL;
        return <<<HTML
<div class="layui-form-item layui-inline">
    <label class="layui-form-label">状态</label>
    <div class="layui-input-inline">
        <select name="status">
            <option value="">全部</option>
            <option value="{$enable}">启用</option>
            <option value="2">禁用</option>
        </select>
    </div>
</div>
HTML;
    }

    public function input(string $title, string $placeholder = ''): string
    {
        return <<<HTML
<div class="layui-form-item layui-inline">
    <label class="layui-form-label">{$title}</label>
    <div class="layui-input-inline">
        <input name="tag" placeholder="{$placeholder}" class="layui-input">
    </div>
</div>
HTML;
    }

    public function submit(): string
    {
        return <<<HTML
 <div class="layui-form-item layui-inline">
    <a class="layui-btn layui-btn-normal" lay-submit>搜索</a>
    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
</div>
HTML;
    }
}