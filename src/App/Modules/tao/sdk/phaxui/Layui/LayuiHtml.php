<?php

namespace App\Modules\tao\sdk\phaxui\Layui;

use App\Modules\tao\Helper\MyMvcHelper;

class LayuiHtml
{

    public function __construct(protected MyMvcHelper $mvc)
    {
    }

    /**
     * 上传组
     * @param string $label 标题，如 “封面”
     * @param string $name input name，如 cover
     * @param array{value:string,type:string,number:string,ext:string,placeholder:string,tip:string,class:string,required:bool} $options 配置信息 value:默认值, number:one单张图片/other多选, ext:png|jpg|ico|jpeg, type:hidden输入框, placeholder:输入框提示文字, tip:提示文字,class:顶层div样式,required:false
     * @return void
     */
    public function upload(string $label, string $name, array $options = ['value' => '', 'type' => 'hidden']): void
    {
        $options = array_merge([
            'value' => '',
            'number' => 'one',
            'ext' => 'png|jpg|ico|jpeg',
            'placeholder' => '图片地址',
            'tip' => '',
            'type' => 'input',
            'class' => '',
            'required' => false,
            'float' => true,
        ], $options);
        $requiredHTML = $options['required'] ? 'required' : '';
        // 输入提示
        $tipDivHTML = $options['tip'] ? '<div class="hint" style="margin-top: 5px;">' . $options['tip'] . '</div>' : '';
        $floatLeft = $options['float'] ? 'display:inline-block;float:left;' : '';
        // 输入框
        $inputHTML = $options['number'] == 'one' ? '<input class="layui-input" name="' . $name . '" id="' . $name . '" type="hidden" style="margin-bottom: 10px;" value="' . $options['value'] . '" placeholder="' . $options['placeholder'] . '" />' : '';
        $editBtnHTML = $options['number'] == 'one' && $options['type'] == 'input' ? '<a class="layui-btn layui-btn-normal data-upload-img-edit" data-name="' . $name . '"><i class="layui-icon layui-icon-edit"></i></a>' : '';

        echo <<<HTML
<div class="btn-image-upload {$options['class']}" style="margin-bottom: 10px; {$floatLeft}">
    <label class="layui-form-label {$requiredHTML}">{$label}</label>
    <div class="layui-input-block"> {$inputHTML}
<div style="margin-bottom:0;">
<a class="layui-btn layui-btn-normal" id="select_{$name}"
data-upload-select="{$name}"
data-upload-number="{$options['number']}"
><i class="fa fa-list"></i></a>
{$editBtnHTML}
    <a class="layui-btn" 
data-upload="{$name}" 
data-upload-number="{$options['number']}"
data-upload-exts="{$options['ext']}"
><i class="fa fa-upload"></i></a>
 
</div>
{$tipDivHTML}
    </div>
</div>
HTML;
    }

    /**
     * 显示图标
     * @param array{value:string} $options value:样式，默认 fa fa-list
     * @return void
     */
    public function icon(array $options = []): void
    {
        $options = array_merge([
            'value' => 'fa fa-list',
        ], $options);
        echo <<<HTML
    <div class="layui-form-item">
        <label class="layui-form-label">选择图标</label>
        <div class="layui-input-inline">
            <input type="text" id="icon" name="icon"
                   class="layui-input" value="{$options['value']}">
        </div>
        <div class="layui-form-label">预览</div>
        <div class="layui-form-mid layui-text-em">
            <i id="preview" class="{$options['value']}"></i>
        </div>
        
   
    </div>  

    <div class="layui-form-item">
    <label class="layui-form-label"></label>
    <div class="layui-input-block">
               <div class="hint">此样式填写于 &lt;i class="图标样式">&lt;/i>，参考资料: <a
                    href="https://layui.dev/docs/2/icon/#examples" target="_blank">Layui Icon</a>，
            <a href="https://fontawesome.com/v4/icons/" target="_blank">FontAwesome 4.7</a></div>
</div>
    </div>
HTML;
    }

    /**
     * 预览 #preview 的 js
     * @return void
     */
    public function iconJs(): void
    {
        echo '<script>';
        echo <<<JS
const preview = $('#preview');
$('#icon').bind('change', function () {
    const v = $(this).val();
    preview.removeClass().addClass(v);
});
JS;
        echo '</script>';
    }

    /**
     * 显示验证码
     * @param array{name:string,placeholder:string,src:string,title:string} $options name:captcha, placeholder:验证码, src:验证码地址, title:点击刷新验证码图片
     * @return string
     */
    public function captcha(array $options = []): string
    {
        $options = array_merge([
            'name' => 'captcha',
            'placeholder' => '验证码',
            'src' => $this->mvc->urlModule('tao/captcha'),
            'title' => '点击刷新验证码图片'
        ], $options);

        return <<<HTML
<div class="layui-form-item">
    <div class="layui-row">
        <div class="layui-col-xs7">
            <div class="layui-input-wrap">
                <div class="layui-input-prefix">
                    <i class="layui-icon layui-icon-vercode"></i>
                </div>
                <input type="text" name="{$options['name']}" value="" lay-verify="required" placeholder="{$options['placeholder']}"
                       lay-reqtext="{$options['placeholder']}" autocomplete="off" class="layui-input" lay-affix="clear">
            </div>
        </div>
        <div class="layui-col-xs5">
            <div style="margin-left: 10px;height: 38px;overflow: hidden;">
                <img style="width: 100%;" title="{$options['title']}"
                     src="{$options['src']}"
                     onclick="this.src='{$options['src']}?t='+ new Date().getTime();">
            </div>
        </div>
    </div>
</div>
HTML;
    }

    /**
     * 面包屑导航
     * @var array
     */
    private array $breadcrumbItems = [];

    /**
     * 设置面包屑导航
     * @param array|string $menus 需要添加的菜单，格式支持 <br/>
     * 标题 <br>
     * [ 'href'=>'链接','text'=>标题 ] 或者 <br/>
     * [ ['href'=>'x1','text'=>'A1'], ['href'=>'x2','text'=>'A2'] ]
     * @return void
     */
    public function addBreadcrumbItem(array|string $menus): void
    {
        if ($menus) {
            if (is_string($menus)) {
                $menus = ['text' => $menus];
            }
            // 格式检查
            if (isset($menus['text'])) {
                $this->breadcrumbItems[] = ['href' => $menus['href'] ?? '', 'text' => $menus['text']];
            } else {
                foreach ($menus as $menu) {
                    $this->breadcrumbItems[] = ['href' => $menu['href'] ?? '', 'text' => $menu['text']];
                }
            }
        }
    }

    /**
     * 输出面包屑导航
     * @return string
     */
    public function breadcrumb(): string
    {
        if ($this->breadcrumbItems) {
            $html = ['<div class="layui-breadcrumb"><a href="/">首页</a>'];
            $items = $this->breadcrumbItems;
            for ($i = 0; $i < count($items) - 1; $i++) {
                $html[] = $items[$i]['href']
                    ? "<a href='{$items[$i]['href']}'>{$items[$i]['text']}</a>"
                    : "<a><cite>{$items[$i]['text']}</cite></a>";
            }
            $last = end($items);
            $html[] = "<a><cite>{$last['text']}</cite></a>";

            $html[] = '</div>';
            return join('', $html);
        } else {
            return '';
        }
    }
}