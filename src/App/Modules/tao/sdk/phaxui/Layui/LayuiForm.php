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
        return $this->wrapFormItem($content,
            name: $name, formItem: $formItem);
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
                             string $style = '',
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
                           string $option = "ON|OFF",
                           string $aux = '',
                           bool   $formItem = true,
                           string $class = '',
                           string $style = '',
    ): string
    {
        $checkedText = $checked ? ' checked' : '';
        return $this->wrapFormItem($this->wrapFormLabel($title, $required) . '<div class="layui-input-inline">
            <input lay-filter="' . $name . '" type="checkbox" name="' . $name . '" lay-skin="switch" ' . $checkedText . ' title="' . $option . '">
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

    public function datetime(string $title, string $name, mixed $value = '',
                             bool   $required = false,
                             bool   $range = false,
                             string $aux = '',
                             string $type = 'datetime',
                             bool   $formItem = true,
    ): string
    {
        $rangeText = $range ? 'true' : 'false';
        $this->mvc->layui()->appendFooterJs(<<<JS
        layui.use(['laydate'], function () {
            var laydate = layui.laydate;
            laydate.render({
                elem: '#{$name}',
                type: '{$type}',
                range: {$rangeText},
            });
        });
JS
        );
        $requiredElem = $required ? '  lay-verify="required"' : '';
        $auxText = $this->wrapAux($aux);
        return $this->wrapFormItem($this->wrapFormLabel($title, $required) . '<div class="layui-input-inline">
            <input type="text" name="' . $name . '" class="layui-input" id="' . $name . '"
                   value="' . $value . '"
                   placeholder="请选择' . $title . '" ' . $requiredElem . '>
        </div>' . $auxText,
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

    public function checkbox(string $title, string $name,
                             bool   $checked = false,
                             bool   $disabled = false,
                             bool   $formItem = true): string
    {
        $checkedText = $checked ? ' checked' : '';
        $disabledText = $disabled ? ' disabled' : '';
        $content = '<input id="' . $name . '" type="checkbox" name="' . $name . '" lay-text="' . $title . '" ' . $checkedText . $disabledText . '>';
        return $this->wrapFormItem($content, name: $name, formItem: $formItem);
    }


}

