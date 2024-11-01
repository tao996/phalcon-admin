<?php

namespace App\Modules\tao\A0\cms\Helper;


class CmsHtmlHelper
{

    public static function header(): void
    {
        \App\Modules\tao\sdk\phaxui\Vue::js();
        echo '<script type="text/javascript">';
        include_once __DIR__ . '/tpl/app.js';
        echo '</script>';

        echo '<style type="text/css">';
        echo <<<CSS
    .cms-block-container {
        display: flex;
        flex-direction: row;
        margin-top: 10px;
    }

    .cms-block-content {
        display: flex;
        flex-direction: column;
        margin: 0 3px 3px 0;
        border: 1px solid grey;
        border-radius: 5px;
        width: 150px;height: 180px;
        overflow: hidden;
        position: relative;
    }
    
    .cms-block-bg {
    height: 150px;
    background-position: center;
    background-size: contain;
    background-repeat: no-repeat;
    }

    .cms-block-image {
        width: 100%;
        /*padding: 5px;*/
    }

    .cms-block-text {
        position: absolute;
        left: 0; bottom: 26px;
        padding: 4px 2px;
        width: 100%;
        background: rgba(240, 240, 240, 0.5);
        overflow: hidden; /* 将超出部分隐藏 */
        text-overflow: ellipsis; /* 显示省略号 */
        white-space: nowrap; /* 禁止换行 */
    }

    .cms-block-actions {
        padding: 2px;
        text-align: center;width: 100%;
        position: absolute;left: 0;bottom: 0;
    }
CSS;
        echo '</style>';
    }

    /**
     * 输出图集 HTML
     * @param bool $formItem 是否输出图片集（此变量不要删除）
     * @return void
     */
    public static function ImageHtml(bool $formItem = true): void
    {
        include_once __DIR__ . '/tpl/image.phtml';
    }

    /**
     * 使用注意：需要放在 layui 事件之前，否则可能出现没有效果
     * @param array|string $items
     * @return void
     */
    public static function ImageJs(array|string $items): void
    {
        $items = self::jsItems($items);
        $prefix = '/m/tao.cms/user.helper';
        echo '<script type="text/javascript">';
        echo <<<JS
const vmImage = vueArray({id:'images',title:'图集',prefix:'{$prefix}',methods:{
    
    descShow: ii=> { // 显示指定项的描述性文字
        const item = vmImage.data.items[ii];
        if (admin.util.isEmpty(item.desc)){
            admin.layer.msg('该图片暂无描述')
        } else {
        admin.layer.alert(item.desc, null, {
            shadeClose: true,
            shade: 0.3
        });
        }
    },
    picker:()=> { // 选择图片
        admin.iframe.open(
            admin.config.url.imageList+'?type=checkbox&key=true', {
                title: '图片选择',
                end: () => {
                   admin.storage.getArray('images', rows =>{
                       vmImage.data.items.push(...rows);
                   });
                }
            });
    }
}, doData:function (data, isArray){
    if (isArray){
        return data.map(cover => {
            return {cover,desc:''}
        })
    }
    return data;
}},{$items})

// 获取图片的 id
function vmImageIds(){
    return vmImage.data.items.map(d => d.id).join(',');
}
function vmImages(items){
    vmImage.data.items = items || [];
}

// 上传事件
layui.upload.render({
    elem: '#album-upload', url: admin.config.url.imageSave,
    exts: 'png|jpg|jpeg', acceptMini: 'file',
    before: function (obj) {
        layui.layer.load();
    },
    done: res => {
        layui.layer.closeAll();
        if (res.code === 0) {
            layui.layer.msg('上传成功', {icon: 1, time: 2000});
            // console.log('image upload done:',res)
            const nImage = res.data;
            const item = {id:nImage.id,url:nImage.url, summary: ''}
            vmImage.data.items.push(item);
            // console.log(vmImage.data.items);
        }
    }
})
JS;
        echo '</script>';
    }

    public static function jsItems(array|string $items): string
    {
        if (is_array($items)) {
            $items = json_encode($items);
        }
        if (empty($items)) {
            $items = '[]';
        }
        return $items;
    }

}