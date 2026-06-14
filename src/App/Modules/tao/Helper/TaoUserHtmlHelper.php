<?php

namespace App\Modules\tao\Helper;

/**
 * 用户专用的 html
 */
class TaoUserHtmlHelper
{

    /**
     * 个人图片上传接口
     * @var string
     */
    public string $imageSaveApi = '/api/m/tao/user.file/save';
    public string $imageList = '/m/tao/user.file/index';

    private bool $hasAppendJs = false;

    public function __construct(protected MyMvcHelper $mvc)
    {
    }

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
            $this->mvc->html()->addFooterContent($this->_uploadJS());
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
        $imageSaveApi = $this->imageSaveApi;
        $imageListPATH = $this->imageList;
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
}