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
            value: $vv->pick('status', 1));
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

    /**
     * 验证码函数
     * <pre>
     * // 点击按钮时弹出验证码窗口
     * // success {Function} 接收一个验证码参数
     * jsCaptcha(captcha => {
     *      const postData = Object.assign(data.field, {captcha});
     *      admin.ajax.post({
     *          url: '<?php echo $vv->urlModule('tao/auth/forgot') ?>', data: postData,
     *      }, function () {
     *          admin.layer.success('邮件已发送，请查收');
     *          setTimeout(function () {
     *          location.href = '<?php echo $vv->urlModule('tao/auth') ?>';
     *      }, 1500);
     * });
     * </pre>
     * @param MyMvcHelper $vv
     * @return void
     */
    static public function jsCaptcha(\App\Modules\tao\Helper\MyMvcHelper $vv): void
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