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

    private array $footerJs = [];

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
                           bool   $formItem = true
    ): string
    {
        $options = [];
        foreach ($vtOptions as $v => $t) {
            $selected = $value == $v ? 'selected' : '';
            $options[] = "<option value='{$v}' $selected>{$t}</option>";
        }

        $content = $this->wrapFormLabel($title, $required) . '
        <div class="layui-input-inline">
            <select lay-filter="' . $name . '" name="' . $name . '" id="' . $name . '" ' . $this->layVerifyRequired($required) . '>
                <option value="">请选择' . $title . '</option>' . join('', $options) . '</select>
        </div>';
        return $this->wrapFormItem($content, $formItem);
    }

    /**
     * 生成一个 input
     * @param string $title
     * @param string $name
     * @param mixed $value
     * @param bool $required
     * @param string $type
     * @param string $aux 辅助文字
     * @param bool $formItem
     * @return string
     */
    public function input(string $title, string $name, mixed $value = '',
                          bool   $required = false,
                          string $type = 'text',
                          string $aux = '',
                          bool   $formItem = true): string
    {
        return $this->wrapFormItem($this->wrapFormLabel($title, $required) . '<div class="layui-input-inline">
            <input ' . $this->layVerifyRequired($required) . ' type="' . $type . '" name="' . $name . '" class="layui-input"
                   value="' . $value . '"
                   placeholder="请填写' . $title . '">
        </div>' . $this->wrapAux($aux), $formItem);
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
                           bool   $formItem = true,
                           string $aux = ''
    ): string
    {
        $checkedText = $checked ? ' checked' : '';
        return $this->wrapFormItem($this->wrapFormLabel($title, $required) . '<div class="layui-input-inline">
            <input type="checkbox" name="' . $name . '" lay-skin="switch" ' . $checkedText . '>
        </div>' . $this->wrapAux($aux), $formItem);
    }

    public function wrapFormItem(string $content, bool $formItem = true): string
    {
        return $formItem ? '<div class="layui-form-item">' . $content . '</div>' : $content;
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
                             bool   $formItem = true,
                             string $type = 'datetime'
    ): string
    {
        $rangeText = $range ? 'true' : 'false';
        $this->footerJs[] = <<<JS
        layui.use(['laydate'], function () {
            var laydate = layui.laydate;
            laydate.render({
                elem: '#{$name}',
                type: '{$type}',
                range: {$rangeText},
            });
        });
JS;
        return $this->wrapFormItem($this->wrapFormLabel($title, $required) . '<div class="layui-input-inline">
            <input type="text" name="' . $name . '" class="layui-input" id="' . $name . '"
                   value="' . $value . '"
                   placeholder="请选择' . $title . '">
        </div>', $formItem);
    }

    public function appendFooterJs(string $content): void
    {
        $this->footerJs[] = $content;
    }

    public function footer(): string
    {
        return '<script>' . join('', $this->footerJs) . '</script>';
    }
}

