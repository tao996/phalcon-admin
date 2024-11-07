<?php

namespace App\Modules\tao\sdk\phaxui;

/**
 * 使用示例<pre>
 *  html:
 *  <textarea ... id="content"></textarea>
 *  js:
 *  TinyMce::init(); // 初始化
 *  tinymce.activeEditor.getContent(); // 获取内容
 *  </pre>
 * @link [文档](https://www.tiny.cloud/docs/tinymce/6/)
 * @link [插件开发](https://www.tiny.cloud/docs/tinymce/6/creating-a-plugin/)
 */
class TinyMce
{
    /**
     * @var array 允许图片域名如 a.com
     * @link https://www.tiny.cloud/docs/tinymce/6/tinymce-and-cors/#editimage_cors_hosts
     */
    public static array $editImageCorsHosts = [];
    public static string $version = '6.8.0';

    /**
     * @param array{language:string,content_css:string} $config
     * @link https://www.tiny.cloud/docs/tinymce/latest/editor-important-options/
     * @return void
     */
    public static function init(array $config = [])
    {
        if (HtmlAssets::isLocal()) {
            echo '<script src="/massets/tao/tinymce/' . self::$version . '/tinymce.min.js"></script>';
        } else {
            echo '<script src="' . HtmlAssets::$cdn . 'tinymce/' . self::$version . '/tinymce.min.js"></script>';
        }
        $config = array_merge([
            'selector' => '#content',
//            'language' => 'zh-Hans', // langs/zh-Hans.js
            'content_css' => '/massets/tao/tinymce.css', // may be you should recover this
            'plugins' => [
                'advlist',
                'autolink',
                'lists',
                'link',
                'image',
                'charmap',
                'preview',
                'anchor',
                'searchreplace',
                'visualblocks',
                'code',
                'fullscreen',
                'insertdatetime',
                'media',
                'table',
                'wordcount'
                // 'editimage', 没有实现
            ],
            'toolbar' => `undo redo | styles | bold italic |
  alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image`,
            'promotion' => false,
            'forced_root_block' => 'div', // 使用 div 而不是 p
//            'force_p_newlines'=>false,
            'force_br_newlines' => true,
            'convert_newlines_to_brs' => false,
            'remove_linebreaks' => true,
            'min_height' => 500,
            // editimage_toolbar: 'rotateleft rotateright | flipv fliph | editimage imageoptions',
            'editimage_cors_hosts' => self::$editImageCorsHosts,
            'image_dimensions' => false, // 移除图片的 width, height
            // image_advtab: true, // 添加样式
            // https://www.tiny.cloud/docs-4x/plugins/image/#image_class_list
            'image_class_list' => [ // 为图片追加样式
                ['title' => '（默认）lazy/full-width', 'code' => 'lazy lazyload full-width'],
                ['title' => '无', 'code' => ''],
            ],
            // https://www.tiny.cloud/docs/plugins/opensource/image/#images_file_types
            'images_file_types' => 'jpg,jpeg,png,gif,JPG,JPEG,PNG,GIF',
            // 图片处理(不支持大写的文件扩展名，如 xxx.JPG 可能上传没有反应)
            'setup' => <<<JS
function(editor: any) {
        editor.on('NodeChange', function (e: any) {
            const tt = e.element.tagName;
            if (tt && typeof tt.upperCase === 'function' && tt.upperCase() === "IMG") {
                // console.log('e:', e.element)
                // 图片懒加载需要 https://github.com/aFarkas/lazysizes 支持
                // <script src="http://afarkas.github.io/lazysizes/lazysizes.min.js" async=""></script>
                // e.element.setAttribute("data-src", e.element.currentSrc);
                // e.element.setAttribute("data-sizes", "auto");
                e.element.setAttribute("loading", "lazy"); // 延迟下载
                // e.element.setAttribute("src", '/images/loading.gif');
            }
        });
    }
JS

        ], $config);
        echo '<script>tinymce.init(' . json_encode($config) . ')</script>';
    }
}