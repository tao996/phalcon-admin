<?php

namespace App\Modules\tao\sdk\phaxui\Layui;

use App\Modules\tao\Helper\MyMvcHelper;

class LayuiForm
{
    public function __construct(protected MyMvcHelper $mvc)
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
        return '<div class="tao-form-radio">'.$this->wrapFormItem($content,
            name: $name, formItem: $formItem).$this->wrapAux($aux).'</div>';
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
                           string $tips = "ON|OFF",
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
        $this->mvc->layui()->appendFooterJs(<<<JS
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

        $this->mvc->layui()->appendFooterJs($laydateJs);

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

    // CSRF 令牌
    public function csrf(): string
    {
        $csrfKey = $this->mvc->security()->getTokenKey();
        $csrfToken = $this->mvc->security()->getToken();
        return '<input type="hidden" name="' . $csrfKey . '" value="' . $csrfToken . '">';
    }

}

