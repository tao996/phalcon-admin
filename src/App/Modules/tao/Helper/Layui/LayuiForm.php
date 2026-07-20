<?php

namespace App\Modules\tao\Helper\Layui;

use App\Modules\tao\Config\Data;
use App\Modules\tao\views\assets\layui\AssetsLayui;
use Phax\Foundation\AppService;

class LayuiForm
{
    /**
     * 个人图片上传接口
     * @var string
     */
    const string IMAGE_SAVE_API = '/api/m/tao/user.file/save';
    const string IMAGE_LIST_API = '/m/tao/user.file/index';

    public function __construct(public AssetsLayui $layui)
    {
    }

    private function layVerifyRequired(bool $required): string
    {
        return $required ? 'lay-verify="required"' : '';
    }


    /**
     * 渲染一个  select
     * @param string $title 标题
     * @param string $name name
     * @param array $vtOptions 通常由 array_column($array, $textKey, $valueKey) 生成
     * @param mixed|null $value
     * @param bool $required
     * @param bool $formItem
     * @return string
     */
    public function select(string $title, string $name,
                           array  $vtOptions = [],
                           mixed  $value = null,
                           bool   $required = false,
                           string $aux = '',
                           bool   $formItem = true,
                           array  $attrs = []
    ): string
    {
        $options = [];
        foreach ($vtOptions as $v => $t) {
            $selected = $value == $v ? 'selected' : '';
            $options[] = "<option value='{$v}' $selected>{$t}</option>";
        }

        return $this->extractedSelect($title, $required, $name, $options, $aux, $formItem, $attrs);
    }

    public function groupSelect(string $title, string $name,
                                array  $groupVtOptions = [], mixed $value = null,
                                bool   $required = false,
                                string $aux = '',
                                bool   $formItem = true)
    {
        $options = [];
        foreach ($groupVtOptions as $label => $vtOptions) {
            $options[] = '<optgroup label="' . $label . '">';
            foreach ($vtOptions as $v => $t) {
                $selected = $value == $v ? 'selected' : '';
                $options[] = "<option value='{$v}' $selected>{$t}</option>";
            }
            $options[] = '</optgroup>';
        }

        return $this->extractedSelect($title, $required, $name, $options, $aux, $formItem);
    }

    public function radio(string $title, string $name,
                          array  $vtOptions = [],
                          mixed  $value = null, bool $required = false,
                          string $aux = '',
                          bool   $formItem = true): string
    {
        $options = [];
        $requiredElem = $required ? '  lay-verify="required"' : '';
        foreach ($vtOptions as $v => $t) {
            $selected = $value == $v ? 'checked' : '';
            $options[] = "<input " . $requiredElem . " lay-filter='" . $name . "' type='radio' name='{$name}' title='{$t}' value='{$v}' $selected>";
        }
        $class = $formItem ? 'layui-input-block' : 'layui-input-inline';

        $content = $this->wrapFormLabel($title, $required) . '<div class="' . $class . '">' . join('', $options) . '</div>';
        return '<div class="tao-form-radio">' . $this->wrapFormItem($content,
                name: $name, formItem: $formItem) . $this->wrapAux($aux) . '</div>';
    }

    /**
     * 生成一个 input
     * @param string $title
     * @param string $name
     * @param mixed $value
     * @param bool $required
     * @param string $type
     * @param string $prefix 前缀内容
     * @param string $subfix 后缀内容
     * @param string $aux 辅助文字
     * @param bool $formItem
     * @param bool $block
     * @return string
     */
    public function input(string $title, string $name, mixed $value = '',
                          bool   $required = false,
                          string $type = 'text',
                          string $placeholder = '',
                          string $prefix = '',
                          string $subfix = '',
                          string $aux = '',
                          string $class = '',
                          bool   $block = false,
                          bool   $formItem = true,
    ): string
    {
        $inputClass = $block ? 'layui-input-block' : 'layui-input-inline';
        $placeholder = $placeholder ?: '请填写' . $title;
        $groupPs = $this->wrapPrefixSuffix('<input lay-affix="clear" ' . $this->layVerifyRequired($required) . ' type="' . $type . '" name="' . $name . '" class="layui-input ' . $class . '"
                   value="' . $value . '"
                   placeholder="' . $placeholder . '">', $prefix, $subfix);
        return $this->wrapFormItem($this->wrapFormLabel($title, $required) . '<div class="' . $inputClass . '">' . $groupPs . '
        </div>' . $this->wrapAux($aux),
            name: $name, formItem: $formItem);
    }

    public function textarea(string $title, string $name, string $value,
                             bool   $required = false,
                             string $placeholder = '',
                             string $class = '',
                             string $style = 'width:600px;',
    ): string
    {

        $placeholder = $placeholder ?: '请填写' . $title;
        $groupPs = $this->wrapPrefixSuffix('<textarea lay-affix="clear"  ' . $this->layVerifyRequired($required) .
            ' name="' . $name . '" class="layui-textarea ' . $class . '" style="' . $style . '" placeholder="' . $placeholder . '">' . $value . '</textarea>');
        return $this->wrapFormItem($this->wrapFormLabel($title, $required) . '<div class="layui-input-block">' . $groupPs . '
        </div>', name: $name);
    }

    public function readonly(string $title, string $name, string $text = '',
                             string $placeholder = '',
                             bool   $block = false,
                             bool   $formItem = true,): string
    {
        $inputClass = $block ? 'layui-input-block' : 'layui-input-inline';
        $groupPs = $this->wrapPrefixSuffix('<input id="input-' . $name . '" style="border:none;color:777;" readonly  type="text" class="layui-input"
                   value="' . $text . '" placeholder="' . $placeholder . '"
                   >');
        return $this->wrapFormItem($this->wrapFormLabel($title) . '<div class="' . $inputClass . '">' . $groupPs . '
        </div>',
            name: $name, formItem: $formItem);
    }

    private function wrapPrefixSuffix(string $content, string $prefix = '', string $suffix = ''): string
    {
        if ($prefix || $suffix) {
            $prefixText = $prefix ? '<div class="llayui-input-split ayui-input-prefix">' . $prefix . '</div>' : '';
            $suffixText = $suffix ? '<div class="layui-input-split layui-input-suffix">' . $suffix . '</div>' : '';
            return '<div class="layui-input-group">' . $prefixText . $content . $suffixText . '</div>';
        }
        return $content;
    }

    public function inputs(InputElement ...$inputs): string
    {
        $options = [];
        foreach ($inputs as $input) {
            $options[] = $this->input($input->title,
                $input->name,
                value: $input->value,
                required: $input->required,
                type: $input->type,
                aux: $input->aux,
                formItem: false,
            );
        }
        return '<div class="layui-form-item">' . join('', $options) . '</div>';
    }

    public function groups(array $items): string
    {
        return '<div class="layui-form-item">' . join('', $items) . '</div>';
    }

    /**
     * 开关
     * @param string $title
     * @param string $name
     * @param bool $checked
     * @param bool $required
     * @param bool $formItem
     * @return string
     */
    public function switch(string $title, string $name, bool $checked = false,
                           bool   $required = false,
                           string $tips = "是|否",
                           string $aux = '',
                           bool   $formItem = true,
                           string $class = '',
                           string $style = '',
    ): string
    {
        $checkedText = $checked ? ' checked' : '';
        return $this->wrapFormItem($this->wrapFormLabel($title, $required) . '<div class="layui-input-inline">
            <input lay-filter="' . $name . '" type="checkbox" name="' . $name . '" lay-skin="switch" ' . $checkedText . ' title="' . $tips . '">
        </div>' . $this->wrapAux($aux),
            name: $name, formItem: $formItem, class: $class, style: $style);
    }

    public function wrapFormItem(string $content,
                                 string $name = '',
                                 bool   $formItem = true,
                                 string $class = '',
                                 string $style = '',
    ): string
    {
        $idName = $name ? ' id="layui-form-item-' . $name . '"' : '';
        $styleText = $style ? ' style="' . $style . '"' : '';
        return $formItem ? '<div class="layui-form-item ' . $class . '" ' . $idName . $styleText . '>' . $content . '</div>' : $content;
    }

    public function wrapFormLabel(string $title, bool $required = false): string
    {
        $requireClass = $required ? ' required' : '';
        return '<label class="layui-form-label' . $requireClass . '">' . $title . '</label>';
    }

    public function wrapAux(string $aux): string
    {
        return $aux ? '<div class="layui-form-mid layui-word-aux">' . $aux . '</div>' : '';
    }

    /**
     * 日期（范围）选择
     * @return string
     */
    public function datesPicker(string $title,
                                string $name = '',
                                string $startName = '',
                                string $endName = '',
                                string $startValue = '',
                                string $type = 'date',
                                string $endValue = '', bool $formItem = true): string
    {
        if ($name) {
            if ($startName == '') {
                $startName = $name . '_start';
            }
            if ($endName == '') {
                $endName = $name . '_end';
            }
        }
        $this->layui->appendFooterJs(<<<JS
        layui.use(['laydate'], function () {
            var laydate = layui.laydate;
            var startPicker = laydate.render({
                elem: '#{$startName}',
                type: '{$type}',
            });
            var endPicker = laydate.render({
                elem: '#{$endName}',
                type: '{$type}',
            });
        });
JS
        );
        return $this->wrapFormItem($this->wrapFormLabel($title) . '
            <div class="layui-input-inline"><input type="text" name="' . $startName . '" class="layui-input" id="' . $startName . '"
                   value="' . $startValue . '" placeholder="开始日期"></div>
            <div class="layui-form-mid layui-hide-xs">-</div>
            <div class="layui-input-inline"><input type="text" name="' . $endName . '" class="layui-input" id="' . $endName . '"
                   value="' . $endValue . '" placeholder="结束日期"></div>', formItem: $formItem);
    }

    public function datetime(string $title, string $name, mixed $value = '',
                             bool   $required = false,
                             string $aux = '',
                             string $type = 'datetime',
                             bool   $preNext = false,
                             bool   $formItem = true,
    ): string
    {

        $laydateJs = <<<JS
        layui.use(['laydate'], function () {
            var laydate = layui.laydate;
            laydate.render({
                elem: '#{$name}',
                type: '{$type}',
            });
        });
JS;

        if ($preNext) {
            $laydateJs .= <<<JS

        document.getElementById('{$name}-prev').onclick = function () {
            var input = document.getElementById('{$name}');
            var parts = input.value.split(/[- :]/);
            var d = new Date(parts[0], (parts[1] || 1) - 1, parts[2] || 1, parts[3] || 0, parts[4] || 0, parts[5] || 0);
            if (!isNaN(d.getTime())) {
                d.setDate(d.getDate() - 1);
                input.value = laydateToStr(d, '{$type}');
            }
        };
        document.getElementById('{$name}-next').onclick = function () {
            var input = document.getElementById('{$name}');
            var parts = input.value.split(/[- :]/);
            var d = new Date(parts[0], (parts[1] || 1) - 1, parts[2] || 1, parts[3] || 0, parts[4] || 0, parts[5] || 0);
            if (!isNaN(d.getTime())) {
                d.setDate(d.getDate() + 1);
                input.value = laydateToStr(d, '{$type}');
            }
        };
        function laydateToStr(d, type) {
            var y = d.getFullYear();
            var m = ('0' + (d.getMonth() + 1)).slice(-2);
            var day = ('0' + d.getDate()).slice(-2);
            if (type === 'date') return y + '-' + m + '-' + day;
            var h = ('0' + d.getHours()).slice(-2);
            var i = ('0' + d.getMinutes()).slice(-2);
            var s = ('0' + d.getSeconds()).slice(-2);
            if (type === 'time') return h + ':' + i + ':' + s;
            if (type === 'month') return y + '-' + m;
            if (type === 'year') return '' + y;
            return y + '-' + m + '-' + day + ' ' + h + ':' + i + ':' + s;
        }
JS;
        }

        $this->layui->appendFooterJs($laydateJs);

        $requiredElem = $required ? '  lay-verify="required"' : '';
        $auxText = $this->wrapAux($aux);
        $style = $preNext ? 'style="margin-right: 0px;"' : '';
        $inputHtml = '<div class="layui-input-inline" ' . $style . '><input type="text" name="' . $name . '" class="layui-input" id="' . $name . '"
                   value="' . $value . '"
                   placeholder="请选择' . $title . '" ' . $requiredElem . '></div>';
        $content = $this->wrapFormLabel($title, $required) . $inputHtml;

        if ($preNext) {
            $content = '
                <button type="button" class="layui-form-label layui-btn " id="' . $name . '-prev" style="color: black;border-right: none;width: 40px;">- 1</button>'
                . $content . '<button type="button" class="layui-btn layui-form-label"  style="color: black;border-left: none;width:40px; margin-right: 10px;" id="' . $name . '-next">+ 1</button>';
        }

        return $this->wrapFormItem($content . $auxText,
            name: $name, formItem: $formItem);
    }

    /**
     * @param string $title
     * @param bool $required
     * @param string $name
     * @param array $options
     * @param string $aux
     * @param bool $formItem
     * @param array $attrs 附加到 select 标签的 HTML 属性，如 ['lay-search' => '']
     * @return string
     */
    protected function extractedSelect(string $title, bool $required, string $name, array $options, string $aux, bool $formItem, array $attrs = []): string
    {
        $extraAttrs = '';
        foreach ($attrs as $attrName => $attrValue) {
            $extraAttrs .= ' ' . $attrName . ($attrValue !== '' && $attrValue !== null ? '="' . htmlspecialchars($attrValue) . '"' : '');
        }
        $content = $this->wrapFormLabel($title, $required) . '
        <div class="layui-input-inline">
            <select lay-filter="' . $name . '" name="' . $name . '" id="' . $name . '" ' . $this->layVerifyRequired($required) . $extraAttrs . '>
                <option value="">请选择' . $title . '</option>' . join('', $options) . '</select>
        </div>' . $this->wrapAux($aux);
        return $this->wrapFormItem($content,
            name: $name, formItem: $formItem);
    }

    public function checkbox(string $title, string $name, string $aux = '',
                             bool   $checked = false,
                             bool   $disabled = false,
                             bool   $formItem = true): string
    {
        $checkedText = $checked ? ' checked' : '';
        $disabledText = $disabled ? ' disabled' : '';
        if ($aux) {
            $aux = '<span class="layui-form-mid layui-word-aux">' . $aux . '</span>';
        }
        $content = '<input id="' . $name . '" type="checkbox" name="' . $name . '" lay-text="' . $title . '" ' . $checkedText . $disabledText . '>' . $aux;
        return $this->wrapFormItem($content, name: $name, formItem: $formItem);
    }

    public function checkboxes(string $title, array $ntOptions, int $value = 1): string
    {
        $content = '';
        $vv = AppService::html();
        foreach ($ntOptions as $name => $text) {
            $checked = $value == $vv->pick($name) ? 'checked' : '';
            $content .= '<input type="checkbox" name="' . $name . '" title="' . $text . '" lay-skin="tag" ' . $checked . ' >';
        }
        return '<div class="layui-form-item"><label class="layui-form-label">' . $title . '</label>
        <div class="layui-input-block">' . $content . '</div>
    </div>';
    }

    // CSRF 令牌
    public function csrf(): string
    {
        $csrfKey = AppService::security()->getTokenKey();
        $csrfToken = AppService::security()->getToken();
        return '<input type="hidden" name="' . $csrfKey . '" value="' . $csrfToken . '">';
    }

    /**
     * 状态选择
     * @param string $title 标题
     * @param string $name 名称
     * @param mixed|null $value 如果为 null，则自动从 pick 中选择
     * @param bool $formItem
     * @return string
     */
    public function status(string $title = '状态', string $name = 'status', mixed $value = null, bool $formItem = true): string
    {
        return $this->select($title, $name,
            vtOptions: Data::MAP_STATUS,
            value: (int)($value == null ? AppService::html()->pick($name, 0) : $value), formItem: $formItem);
    }

    /**
     * @return string 表单提交/重置按钮
     */
    public function submit(): string
    {
        return '<div class="hr-line"></div>
    <div class="layui-form-item text-center">
        <button type="submit" class="layui-btn layui-btn-normal layui-btn-sm" lay-submit>确认</button>
        <button type="reset" class="layui-btn layui-btn-primary layui-btn-sm">重置</button>
    </div>';
    }


    private bool $hasAppendJs = false;


    /**
     * 专用上传组件
     * <pre>
     * // html 代码
     * upload('用户头像', 'head_img',['value' => $user->head_img, 'class' => 'mb10']);
     * </pre>
     * @param string $label 标题，如 “封面”
     * @param string $name input name，如 cover
     * @param string $type 类型，默认为 hidden
     * @param string $value 值，只有 type=hidden 时才会看到
     * @param string $placeholder 输入提示，只有在 type=input 时才会显示出来
     * @param string $tip
     * @param string $ext
     * @param string $class
     * @param bool $required
     * @param bool $multiple
     * @param bool $float
     * @return string
     */
    public function upload(string $label, string $name,
                           string $type = 'hidden',
                           string $value = '',
                           string $placeholder = '图片地址',
                           string $tip = '',
                           string $ext = 'png|jpg|ico|jpeg',
                           string $class = '', bool $required = false, bool $multiple = false, bool $float = true): string
    {
        $options = [
            'value' => $value,
            'ext' => $ext,
            'placeholder' => $placeholder,
            'tip' => $tip,
            'type' => $type,
            'class' => $class,
            'required' => $required,
            'float' => $float,
            'number' => $multiple ? 9 : 1,
        ];
        $requiredHTML = $options['required'] ? 'required' : '';
        // 输入提示
        $tipDivHTML = $options['tip'] ? '<div class="hint" style="margin-top: 5px;">' . $options['tip'] . '</div>' : '';
        $floatLeft = $options['float'] ? 'display:inline-block;float:left;' : '';
        // 输入框
        $inputHTML = !$multiple ? '<input class="layui-input" name="' . $name . '" id="' . $name . '" type="hidden" style="margin-bottom: 10px;" value="' . $options['value'] . '" placeholder="' . $options['placeholder'] . '" />' : '';
        $editBtnHTML = !$multiple && $options['type'] == 'input' ? '<a class="layui-btn layui-btn-normal data-upload-img-edit" data-name="' . $name . '"><i class="layui-icon layui-icon-edit"></i></a>' : '';
        if (!$this->hasAppendJs) {
            $this->hasAppendJs = true;
            AppService::html()->addFooterContent($this->_uploadJS());
        }
        return <<<HTML
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
     * 自定义图片上传
     * @link https://layui.dev/docs/2/upload/
     */
    // render:function (){
    //     const postOptions = {
    //         elem: this,
    //         url: '', // 上传接口
    //         field: 'file', // 文件域字段名
    //         data: {}, // 传递给上传接口的额外数据，如 {id: '123'}
    //         headers: headers, // 上传接口的请求头。如 headers: {token: 'abc123'}
    //         dataType: 'json', // 服务端返回的数据类型，如：text,json,xml 等
    //         accept: uploadAccept,  // 指定允许上传时校验的文件类型，可选值 为：image, file, video, audio
    //         acceptMime: uploadAcceptMime, // 规定打开系统的文件选择框时，筛选出的文件类型，多个 MIME 类型可用逗号隔开。示例：
    //         // acceptMime: 'image/*'` // 筛选所有图片类型
    //         // acceptMime: 'image/jpeg, image/png` // 只筛选 jpg,png 格式图片
    //         exts: uploadExts, // 允许上传的文件后缀。一般结合 accept 属性来设定。假设 accept: 'file' 类型时，那么设置 exts: 'zip|rar|7z' 即代表只允许上传压缩格式的文件。
    //         auto: true, // 选完文件后自动上传
    //         size: 0, // 上传文件大小，单位 KB
    //         multiple: multiple, // 是否允许多文件上传
    //         number:0, // 只有在 multiple: true 时才有效
    //         drag: true, // 支持拖拽上传
    //     };
    //     console.log('文件上传');
    // },
    private function _uploadJS(): string
    {
        $liHTML = '<li>
<a><img src="${v}"></a>
<small class="uploads-delete-tip bg-red badge" data-upload-delete="${uploadName}" data-upload-url="${v}" data-upload-separator="${uploadSeparator}">×</small>
</li>';
        $imageSaveApi = self::IMAGE_SAVE_API;
        $imageListPATH = self::IMAGE_LIST_API;
        return <<<JS1
<script>
   const userUpload = {
        /**
         * 添加 圖片
         * @param {string} url 圖片地址
         * @param {string} name 操作來源 upload, picker
         */
        after: function (url, name) {
            console.log('userHtmlHelper upload.after:', url, name)
        },
        /**
         * 移除圖片
         * @param url
         */
        remove: function (url) {
        },
        /**
         * 執行上傳
         * 接收上传图片回调 `userUpload.run().after = function (url,name) { 你的代码 }`
         * https://layui.dev/docs/2/upload/
         * @return this
         */
        run: function () {
            /**
             * @var admin {Object} 配置信息
             * @link src/public/extends/tao/layui/plugs/easy-admin/easy-admin.js upload:1363
             */
            const uploadList = document.querySelectorAll("[data-upload]"); // 上传
            const uploadSelectList = document.querySelectorAll("[data-upload-select]"); // 选择

            if (uploadList.length > 0) {
                layui.jquery.each(uploadList, function (i, v) {
                    // 上传配置
                    const jThis = $(this);
                    const uploadExts = jThis.attr('data-upload-exts') || 'png|jpg|jpeg',
                        uploadName = jThis.attr('data-upload'),
                        uploadNumber = parseInt(jThis.attr('data-upload-number')) || 1, // 可选择图片数量
                        uploadSeparator = jThis.attr('data-upload-separator') || '|', // 分割符
                        uploadAccept = jThis.attr('data-upload-accept') || 'file',
                        uploadAcceptMime = jThis.attr('data-upload-mimetype') || '';
                    const elem = "input[name='" + uploadName + "']",
                        multiple = uploadNumber > 1,
                        uploadElem = this;
                    const headers = admin.config.ajax.headers();
                    // 监听上传事件
                    layui.upload.render({
                        elem: this,
                        url: '{$imageSaveApi}', // 上传接口
                        field: 'file', // 文件域字段名
                        exts: uploadExts,
                        accept: uploadAccept,
                        acceptMime: uploadAcceptMime,
                        multiple: multiple,
                        headers: headers,
                        // 文件提交上传前的回调函数。返回的参数同 choose
                        before: function (obj) {
                            layui.layer.load();
                        },
                        done: function (res) {
                            layui.layer.closeAll();
                            // console.log('upload result:',res)
                            if (res.code === 0 || res.code === 200) {
                                let url = res.data.url;
                                if (multiple) { // 多张上传
                                    var oldUrl = $(elem).val();
                                    if (oldUrl !== '') {
                                        url = oldUrl + uploadSeparator + url;
                                    }
                                }

                                $(elem).val(url);
                                $(elem).trigger('input');
                                layui.layer.msg('上传成功', {
                                    icon: 1, time: 2000
                                })
                                userUpload.after(url, 'upload');
                            } else {
                                admin.layer.errorAlert(res.msg);
                            }
                        },
                        error: function () {
                            setTimeout(function () {
                                layui.layer.closeAll()
                            }, 3000)
                        },
                        complete: function () {
                            admin.config.ajax.refreshHeaders('post');
                        }
                    })

                    // 监听上传 input 值变化;如果有值，则显示出图片
                    $(elem).bind('blur', function () {
                        userUpload.after($(this).val(), 'blur');
                    });
                    $(elem).bind("input propertychange", function (event) {
                        const urlString = $(this).val(),
                            urlArray = urlString.split(uploadSeparator),
                            uploadIcon = $(uploadElem).attr('data-upload-icon') || "file";

                        $('#bing-' + uploadName).remove();
                        if (urlString.length > 0) {
                            const parant = $(this).parent('div');
                            let liHtml = '';
                            $.each(urlArray, function (i, v) {
                                liHtml += `{$liHTML}`;
                            });
                            parant.after('<ul id="bing-' + uploadName + '" class="layui-input-block layuimini-upload-show">' + liHtml + '</ul>');
                        }

                    });

                    // 非空初始化，图片显示
                    if ($(elem).val() !== '') {
                        $(elem).trigger('input')
                    }
                });

                // 监听图片的删除事件
                layui.jquery('body').on('click', '[data-upload-delete]', function () {
                    const uploadName = $(this).attr('data-upload-delete'),
                        deleteUrl = $(this).attr('data-upload-url'),
                        sign = $(this).attr('data-upload-sign');

                    layui.layer.confirm('确定要删除吗？', function (index) {
                        const elem = "input[name='" + uploadName + "']";
                        const currentUrl = $(elem).val();
                        let url = '';
                        if (currentUrl !== deleteUrl) {
                            url = currentUrl.search(deleteUrl) === 0 ? currentUrl.replace(deleteUrl + sign, '') : currentUrl.replace(sign + deleteUrl, '');
                            $(elem).val(url);
                            $(elem).trigger("input");
                        } else {
                            $(elem).val(url);
                            $('#bing-' + uploadName).remove();
                        }
                        layui.layer.close(index);
                        userUpload.remove(url);
                    });
                    return false;
                });
            }
// 图片选择
            if (uploadSelectList.length > 0) {
                layui.jquery.each(uploadSelectList, function (i, v) {
                    const uploadName = $(this).attr('data-upload-select'),
                        uploadNumber = parseInt($(this).attr('data-upload-number')) || 1,
                        uploadSeparator = $(this).attr('data-upload-separator') || '|';

                    const selectCheck = uploadNumber > 1 ? 'checkbox' : 'radio',
                        inputElem = $("input[name='" + uploadName + "']"),
                        uploadElem = $(this).attr('id');

                    $('#' + uploadElem).off('click').on('click', function () {
                        admin.iframe.open(
                            '{$imageListPATH}?type=' + selectCheck, {
                                title: '图片选择',
                                end: function () {
                                    admin.storage.getArray('images', images => {
                                        const url = images.join(uploadSeparator);
                                        inputElem.val(url);
                                        inputElem.trigger("input");
                                        admin.layer.success('选择成功');
                                        userUpload.after(url, 'picker');
                                    });
                                }
                            });
                    });
                });
                layui.jquery('.data-upload-img-edit').bind('click', function () {
                    const name = this.getAttribute('data-name');
                    const inputEle = $('input[name=' + name + ']');
                    layer.prompt({
                        formType: 0,
                        value: inputEle.val(),
                        title: '请输入图片地址',
                    }, function (value, index, elem) {
                        inputEle.val(value);
                        inputEle.trigger('input');
                        userUpload.after(value, 'edit');
                        layer.close(index); // 关闭层
                    });
                })
            }
            return this;
        }
    };
   userUpload.run();
</script>
JS1;
    }


    /**
     * 验证码函数
     * <pre>
     * // 点击按钮时弹出验证码窗口
     * // success {Function} 接收一个验证码参数
     * jsCaptcha(captcha => {
     *      const postData = Object.assign(data.field, {captcha});
     *      admin.ajax.post({
     *          url: '<?php echo \Phax\Foundation\AppService::urlModule('tao/auth/forgot') ?>', data: postData,
     *      }, function () {
     *          admin.layer.success('邮件已发送，请查收');
     *          setTimeout(function () {
     *          location.href = '<?php echo \Phax\Foundation\AppService::urlModule('tao/auth') ?>';
     *      }, 1500);
     * });
     * </pre>
     * @return void
     */
    public function jsCaptcha(): void
    {
        static $noWrite = true;
        if ($noWrite) {
            $noWrite = false;
            echo <<<CAPTCHA
<script type="text/javascript">
function jsCaptcha(success) {
    layui.layer.open({
        type: 1, area: '250px', resize: false, shadeClose: true,
        offset: Math.max(((window.innerHeight - 400) / 2), 100) + 'px',
        title: '验证码', content: `<div class="layui-form" lay-filter="form-captcha" style="margin: 16px;">
<div>
<img style="width: 218px;height: 50px;" src="/m/tao/captcha" onclick="this.src='/m/tao/captcha?t='+ new Date().getTime();" id="formCaptcha">
<div style="font-size: 0.9em;font-weight: bold" lay-on="refreshCaptcha">点击图片刷新验证码（不区分大小写）</div>
</div>
<div style="margin: 10px 0;">
<input type="text" name="captcha" value="" lay-verify="required" 
placeholder="请填写图片上的验证码" lay-reqtext="请填写验证码" maxlength="4"
autocomplete="off" class="layui-input" lay-affix="clear">
</div>
<div class="layui-form-item">
<button class="layui-btn layui-btn-fluid" lay-submit lay-filter="submit-captcha">确定</button>
</div>
</div>
`, success: function (layero, index) {
            layui.form.render();
            layui.form.on('submit(submit-captcha)', function (data) {
                const captcha = data.field.captcha;
                layer.close(index)
                success(captcha)
                return false;
            });

            layui.util.on({
                refreshCaptcha: function () {
                    document.getElementById('formCaptcha').src = '/m/tao/captcha?t=' + new Date().getTime();
                }
            })
        }
    })
}
</script>
CAPTCHA;
        }

    }
}

