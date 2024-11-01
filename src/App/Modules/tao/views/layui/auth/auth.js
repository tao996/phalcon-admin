const auth = {
    /**
     * 登录成功之后
     * @param options
     */
    afterLogin: function (options = {}) {
        setTimeout(function () {
            const _redirect = auth.getRedirect();
            location.href = _redirect ? decodeURIComponent(_redirect) : '/';
        }, 1000)
    },
    /**
     * 注册成功之后
     * @param {{url:string}} options
     */
    afterSignup: function (options = {}) {
        admin.layer.alert('注册成功，前往登录', function () {
            window.location.href = options.url || '/';
        })
    },
    /**
     * 获取回调地址
     * @return {string}
     */
    getRedirect: function () {
        const url = layui.url();
        return url.search && url.search._redirect ? url.search._redirect : '';
    },
    createUrl: function (prefix, action) {
        const back = prefix + action;
        const _redirect = this.getRedirect();
        if (_redirect) {
            return back + '&_redirect=' + auth.getRedirect();
        }
        return back;
    },
    /**
     * 绑定各种按钮
     * @param {{prefix:string}} config prefix通常为 moduleUrl('tao/auth/')
     */
    bindButtons: function (config) {
        admin.util.layOn({
            // 账号密码登录
            index: function () {
                location.href = auth.createUrl(config.prefix, 'index')
            },
            // 验证码登录
            signin: function () {
                location.href = auth.createUrl(config.prefix, 'signin')
            },
            // 注册
            signup: function () {
                location.href = auth.createUrl(config.prefix, 'signup')
            },
            // 忘记密码
            forgot: function () {
                location.href = auth.createUrl(config.prefix, 'forgot')
            }
        })
    }
}