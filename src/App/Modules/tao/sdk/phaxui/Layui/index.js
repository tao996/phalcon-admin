/**
 * @link https://layui.dev/docs/2
 */
const admin = {
    /**
     * 初始化配置
     */
    config: {
        debug: false,
        url: {
            imageSave: '/api/m/tao/user.file/save',
            imageList: '/m/tao/user.file/index',
        },
        ajax: {
            headers: function () {
                if (!admin.util.isEmpty(window.CONFIG) && !admin.util.isEmpty(window.CONFIG.CSRF_TOKEN)) {
                    return {'X-CSRF-TOKEN': window.CONFIG.CSRF_TOKEN}
                }
                return {};
            },
            refreshHeaders: function (method = 'get') {
                if (['post', 'put', 'delete'].includes(method)) {
                    console.log('try to refresh csrf-token');
                    // @todo 刷新 csrf-token
                }
            }
        }
    },
    debug: function () {
        return admin.config.debug || false;
    },
    /**
     * 覆盖 admin 默认配置
     * @param option {Object}
     */
    assign: function (option) {
        Object.assign(admin.config, option)
    },
    /**
     * 工具类
     */
    util: {
        isEmpty: function (data) {
            if (data === null || data === undefined) {
                return true;
            }
            switch (typeof data) {
                case "undefined":
                    return true;
                case "number":
                    return data === 0;
                case "string":
                    data = data.trim();
                    return data === '0' ||
                        data === 'null' ||
                        data === '' ||
                        data === 'undefined' ||
                        data === '0001-01-01T00:00:00Z' ||
                        data === '0001-01-01 00:00:00';
                case "object":
                    const r = JSON.stringify(data);
                    return r === '{}' || r === '[]'
                case "boolean":
                    return !data;
                default:
                    return !!data;
            }
        },
        /**
         * 判断是否为手机
         * @returns {boolean}
         * @link https://layui.dev/docs/2/base.html#device
         */
        checkMobile: function () {
            return layui.device('mobile');
        },
        /**
         * 拼接成 URL 请求参数
         * @link https://layui.dev/docs/2/base.html#url
         * @param dict {Object}
         * @returns {string}
         */
        concatQuery: function (dict) {
            var d = [];
            for (var key in dict) {
                d.push(key + '=' + dict[key]);
            }
            return d.join('&');
        },
        /**
         * 追加或替换 url 中的请求参数
         * @param url {string} URL 地址
         * @param name {string} 请求参数
         * @param value {any} 值
         * @return {string}
         */
        appendOrReplaceQueryParam: function (url, name, value) {
            const separator = url.includes('?') ? '&' : '?';
            const append = `${name}=${value}`
            if (url.includes(name + '=')) {
                const re = new RegExp(`${name}=\\d+`)
                return url.replace(re, append);
            } else {
                return url + separator + append;
            }
        },
        /**
         * @param {Object} binds 绑定操作
         * @link https://layui.dev/docs/2/util/#on
         * @example
         * <button class="layui-btn" lay-on="e1">事件</button>
         * layui.util.on({ e1:function(othis){
         *      console.log(othis.html());
         *      console.log(this.getAttribute('data-kind'));
         * });
         */
        layOn: function (binds) {
            layui.util.on('lay-on', binds);
        },
        /**
         * 从 obj 中挑选出需要的值
         * @param {Object} obj 对象
         * @param props
         * @return {{}}
         */
        pick: function (obj, ...props) {
            return Object.assign({}, ...props.map(prop => ({[prop]: obj[prop]})));
        },
        toDateString: function (phpTimestamp, format = 'yyyy-MM-dd') {
            if (phpTimestamp < 1) {
                return '';
            }
            return layui.util.toDateString(phpTimestamp * 1000, format)
        }
    },

    /**
     * 消息提示
     * @link https://layui.dev/docs/2/layer/#options.callback
     */
    layer: {
        image: function (src, title = '圖片展示') {
            layer.photos({
                photos: {
                    title, start: 0, data: [{src}]
                }
            })
        },
        /**
         * 成功提示
         * @public
         * @param msg {string} 提示信息
         * @param {function} [callback]
         */
        success: function (msg, callback) {
            return layui.layer.msg(msg, {
                icon: 1,
                scrollbar: false,
                time: 2000,
                shadeClose: true
            }, this._createCallback_(callback));
        },
        /**
         * 失败提示
         * @public
         * @param msg {string}
         * @param {function} [callback]
         */
        error: function (msg, callback) {
            return layui.layer.msg(msg, {
                icon: 2,
                scrollbar: false,
                time: 3000,
                shadeClose: true
            }, this._createCallback_(callback))
        },
        /**
         * 警告消息框
         * @public
         * @link https://layui.dev/docs/2/layer/#options
         * @param msg {string}
         * @param {function} [callback]
         * @param {{title:string}} [options]
         */
        alert: function (msg, callback, options = {}) {
            return layui.layer.alert(msg, Object.assign({
                scrollbar: false
            }, options), function (index) {
                layui.layer.close(index);
                if (typeof callback == "function") {
                    callback();
                }
            });
        },
        /**
         * 询问框
         * @public
         * @param msg {string}
         * @param {function} [ok] 确认操作回调
         * @param {function} [no] 取消操作回调
         */
        confirm: function (msg, ok, no) {
            return layui.layer.confirm(msg, {title: '操作确认', btn: ['确认', '取消']}, function (index) {
                if (typeof ok === 'function') {
                    ok(index);
                }
                admin.layer.close(index);
            }, function (index) {
                if (typeof no === 'function') {
                    no(index);
                }
                admin.layer.close(index)
            });
        },
        /**
         * 消息提示框(贴士层)
         * @param msg {string}
         * @param time {number} 秒数，默认为 3
         * @param {function} [callback]
         */
        tips: function (msg, time, callback) {
            return layui.layer.msg(msg, {
                time: (time || 3) * 1000,
                end: this._createCallback_(callback),
                shadeClose: true,
            })
        },
        msg: function (message) {
            return layui.layer.msg(message);
        },
        /**
         * 加载提示，需要手动关闭
         * @param msg {string}
         * @param {function} [callback]
         */
        loading: function (msg = '', callback = null) {
            return msg ? layui.layer.msg(msg, {
                icon: 16,
                scrollbar: false,
                time: 0,
                end: this._createCallback_(callback),
            }) : layui.layer.load(2, {
                time: 0,
                scrollbar: false,
                end: this._createCallback_(callback),
            })
        },
        /**
         * 加载提示，并自动关闭
         * @param seconds {number} 秒数，默认为2
         */
        load: function (seconds = 2) {
            const index = layer.load(2, {time: seconds * 1000}); // 加载图标风格，并设置最长等待 10 秒
            layer.close(index);
        },
        // 关闭消息框
        close: function (index = 0) {
            if (index === 0) {
                layui.layer.closeAll()
            } else {
                return layui.layer.close(index);
            }
        },
        /**
         * 生成一个回调函数
         * @param {function} [callback]
         * @returns {*|(function(*): void)}
         * @private
         */
        _createCallback_: function (callback) {
            if (typeof callback === "function") {
                return callback;
            } else {
                return function (index) {
                    if (!admin.util.isEmpty(index)) {
                        this.close(index);
                    }
                }
            }
        }
    },
    /**
     * ajax 请求
     */
    ajax: {
        _isPosting: false,
        /**
         * 请求方法
         * @param option {{url?: string}}
         * @param {function} [ok] 返回成功回调
         * @param {function} [no] 返回错误回调
         * @param {function} [complete] 完成回调
         * @param {function} [ex] 错误回调味
         */
        get: function (option, ok, no, complete, ex) {
            this.ajax('get', option, ok, no, complete, ex)
        },
        /**
         * 请求方法
         * @param option {{url?:string,data:Object}}
         * @param {function} [ok] 返回成功回调
         * @param {function} [no] 返回错误回调
         * @param {function} [complete] 完成回调
         * @param {function} [ex] 错误回调
         */
        post: function (option, ok, no, complete, ex) {
            this.ajax('post', option, ok, no, complete, ex)
        },
        // layui.debounce(fn, wait) 防抖，layui.throttle(fn, wait) 节流
        /**
         *
         * @param option {{url?:string,data:Object}}
         * @param {function} [ok] 返回成功回调
         * @param {function} [no] 返回错误回调
         * @param {function} [complete] 完成回调
         * @param {function} [ex] 错误回调
         */
        postLimit: function (option, ok, no, complete, ex) {
            if (this._isPosting) {
                layui.layer.msg('操作过于频繁，请稍等一会');
                return;
            }
            this._isPosting = true;
            this.ajax('post', option, ok, no, function (data) {
                admin.ajax._isPosting = false;
                if (typeof complete == 'function') {
                    complete(data);
                }
            }, function (obj) {
                admin.ajax._isPosting = false;
                if (typeof ex === 'function') {
                    ex(obj);
                }
            })
        },
        /**
         * 请求方法
         * @param type {string} get|post|put|delete
         * @param option {{url?:string,data:Object,dataType?:string,}}
         * @param {function} [ok] 返回成功回调
         * @param {function} [no] 返回错误回调
         * @param {function} [complete] 完成回调
         * @param {function} [ex] 错误回调味
         */
        ajax: function (type, option, ok, no, complete, ex) {
            option = Object.assign({
                url: '',
                data: {},
                dataType: 'json',
                contentType: "application/x-www-form-urlencoded; charset=UTF-8",
                timeout: 60000,
                statusName: 'code',
                statusCode: 0,
            }, option)
            if (option.url === '') {
                option.url = this.apiURL();
            }
            const loadIndex = admin.layer.loading('加载中')
            layui.jquery.ajax({
                url: option.url,
                type: type || 'get',
                data: option.data,
                dataType: option.dataType,
                contentType: option.contentType,
                timeout: option.timeout,
                headers: admin.config.ajax.headers(),
                success: function (res) {
                    if ([0, 200].includes(res[option.statusName])) {
                        if (typeof ok === 'string') {
                            admin.layer.success(ok);
                        } else if (res.msg) {
                            if (typeof ok == 'function') {
                                admin.layer.success(res.msg, () => {
                                    ok(res);
                                });
                                return;

                            }
                            admin.layer.success(res.msg);
                        }
                        if (typeof ok == 'function') {
                            setTimeout(function () {
                                ok(res);
                            }, 500)
                        }
                    } else {
                        admin.layer.error(typeof no == 'string' ? no : res.msg);
                        typeof no == 'function' && no(res);
                    }
                },
                error: function (xhr, textstatus, thrown) {
                    admin.layer.error('Status:' + xhr.status + '，' + xhr.statusText + '，请稍后再试！', function () {
                        if (typeof ex === 'function') {
                            ex(this);
                        }
                    });
                },
                complete: function (xhr) {
                    admin.layer.close(loadIndex);
                    admin.config.ajax.refreshHeaders(option.type);
                    try {
                        const data = JSON.parse(xhr.responseText).data;
                        if (typeof complete === 'function') {
                            complete(data);
                        }
                    } catch (e) {
                        console.error('响应结果不符合规范:', option.url);
                    }
                }
            })
        },
        apiURL: function () {
            return location.origin + '/api' + location.pathname + location.search
        }
    },
    /**
     * 表单
     * @link https://layui.dev/docs/2/form/
     */
    form: {
        /**
         * 当被 lay-filter 标记的元素(需要添加 lay-submit)被点击时，获取表单的值，并传到回调函数中；监听其它事件建议使用 admin.form.on
         * @param {string} layFilter lay-filter 名称，如 demo-click
         * @param {function} action 回调函数，接收表单中所填写的数据
         */
        onSubmit: function (layFilter, action) {
            layui.form.on(`submit(${layFilter})`, function (data) {
                const dataField = data.field; // 表单全部的值
                action(dataField);
                return false;
            })
        },
        /**
         * lay-filter 事件 <pre>
         *     单选框事件 form.on('radio(filter)', callback); // filter 为单选框元素对应的 lay-filter 属性值
         *     form.on('select', function(data){console.log(data);});// 指向所有 select 组件的选择事件
         *     form.on('select(test)', function(data){console.log(data);});// 指向元素为 `<select lay-filter="test"></select>` 的选择事件
         * </pre>
         * @link https://layui.dev/docs/2/form/#on
         * @param {string} layFilterName lay-filter 对应的值
         * @param {function} action 回调函数 data.elem.value 选中的值, data.elem.checked 是否选中, data.othis jQuery对象
         * @param {string} eleKind 元素类型，支持 select,checkbox,switch 开关风格, radio,submit；如果为空，则是全部元素
         */
        on: function (layFilterName, action, eleKind = '') {
            const name = admin.util.isEmpty(eleKind) ? layFilterName : `${eleKind}(${layFilterName})`
            layui.form.on(name, function (data) {
                try {
                    action(data);
                } catch (e) {
                    console.error(e);
                }
                return false;
            });
        },
        /**
         * 绑定第一个 lay-submit 按钮，以提交第一个表单
         * @param {function|string} ok
         * @param {function} [postDataCallback] 如果返回 false 则取消表单提交；否则返回提交的数据
         * @param {{url:string|Function,no?:function,complete?:function,ex?:function}} [options]
         * @returns {boolean}
         */
        submitFirst: function (ok, postDataCallback, options = {url: ''}) {
            const formList = document.querySelectorAll("[lay-submit]");
            if (formList.length > 0) {
                const f = layui.jquery(formList[0]);

                // 表格搜索不做自动提交
                if (f.attr('data-type') === 'tableSearch') { // 表格刷新
                    return false;
                }
                // 自动添加过滤器
                let filter = f.attr('lay-filter');
                if (admin.util.isEmpty(filter)) {
                    filter = 'save_form_1';
                    f.attr('lay-filter', filter);
                }
                layui.form.on('submit(' + filter + ')', function (data) {
                    let postData = data.field;
                    if (typeof postDataCallback == "function") {
                        try {
                            postData = postDataCallback(postData);
                        } catch (e) {
                            console.error(e);
                            return false;
                        }
                    }
                    if (postData === false || null === postData) {
                        console.warn('取消了表单提交');
                        return false;
                    }
                    let url = '';
                    switch (typeof options.url) {
                        case "string":
                            url = options.url;
                            break;
                        case 'function':
                            url = options.url()
                            break;
                    }

                    admin.form.request(url, postData, ok, options.no, options.complete, options.ex)
                    return false;
                })

            }
        },
        request: function (url, data, ok, no, complete, ex) {
            if (url === undefined || url === '' || url === null) {
                url = location.origin + '/api' + location.pathname + location.search
            }
            admin.ajax.post({
                url, data
            }, function (data) {
                ok(data)
            }, no, complete, ex)
        },

        updateValueByName: function (kvs) {
            for (const name in kvs) {
                document.getElementsByName(name)[0].value = kvs[name];
            }
        },

        /**
         * 表单赋值
         * @link https://layui.dev/docs/2/form/#val
         * @param {string} layFilterName 添加在 form 上的 lay-filter
         * @param {Object} values 赋值
         */
        patch: function (layFilterName, values) {
            layui.form.val(layFilterName, values);
        },
        /**
         * 点击按钮时弹出验证码窗口
         * @param success {Function} 接收一个验证码参数
         */
        captcha: function (success) {
            layer.open({
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
        },
        /**
         * 为 class=bind_date或bind_datetime 的绑定时间
         */
        bindDate: function () {
            layui.laydate.render({
                elem: '.bind_date'
            });

            layui.laydate.render({
                elem: '.bind_datetime',
                type: 'datetime',
            })
        }
    },
    /**
     * 页面处理
     */
    page: {
        /**
         * 刷新当前层所在父层数据
         * @param {{refreshTable?:boolean,refreshFrame?:boolean}} option
         * @returns {boolean}
         */
        closeCurrentOpen: function (option) {
            // console.log('admin.page.closeCurrentOpen',option)
            option = Object.assign({
                refreshTable: false,
                refreshFrame: false,
            }, option || {});

            const parentIndex = parent.layer.getFrameIndex(window.name);
            parent.layer.close(parentIndex);
            if (option.refreshTable) {
                // todo 获取表格 id
                parent.layui.table.reload()
            }
            if (option.refreshFrame) {
                parent.location.reload();
            }
            return true;
        },
        inIframe: function () {
            try {
                return window.self !== window.top;
            } catch (e) {
                return true;
            }
        },
        /**
         * 打开新页面
         * @param title {string} 标题
         * @param url {string} 链接
         * @param end {Function} 关闭页面回调函数
         */
        open: function (title, url, end) {
            admin.iframe.open(url, {
                title, end
            });
        }
    },
    iframe: {
        /**
         * @link https://layui.dev/docs/2/layer/#options
         * @param {string} url
         * @param {{title?:string,width?:string,height?:string,full?:boolean,end?:Function,resize?:boolean}} options
         */
        open: function (url, options = {}) {
            if (admin.util.isEmpty(options.width) && admin.util.isEmpty(options.height)) {
                const width = window.innerWidth, height = window.innerHeight;
                // const width = document.body.clientWidth, height = document.body.clientHeight;

                if (width >= 1000 && height >= 600) {
                    options.width = '1000px';
                    options.height = '600px';
                } else {
                    options.width = '100%';
                    options.height = '100%'
                }
                // console.log('iframe:', width, height, '=>', options.width, options.height)
            }
            if (options.full === true) {
                options.width = '100%';
                options.height = '100%';
            }
            let iFrameIndex = layui.layer.open({
                title: options.title || '', type: 2,
                area: [options.width, options.height],
                content: url,
                shadeClose: true,
                maxmin: true,
                end: function () {
                    if (typeof options.end === 'function') {
                        options.end(iFrameIndex)
                        iFrameIndex = null;
                    }
                }
            })
            if (options.resize) {
                $(window).on("resize", function () {
                    index && layer.full(iFrameIndex);
                })
            }

        },
        /**
         * 从父窗口中移除
         */
        closeFromParent: function (refresh = false) {
            const index = parent.layer.getFrameIndex(window.name);
            parent.layer.close(index);
            if (refresh) {
                localStorage.setItem('tao.refresh', "yes");
            }
        },
        hasRefresh: function (action) {
            if (localStorage.getItem('tao.refresh') === 'yes') {
                if (typeof action === 'function') {
                    action();
                }
                localStorage.removeItem('tao.refresh');
            }
        },
        // 更新父菜单
        updateParentMenu: function () {
            try {
                parent.notifyUpdateMenu();
            } catch (err) {
                console.log('parent iframe not find, update menu failed');
            }
        },
        addParentTab: function (title, href, id = 0) {
            try {
                parent.appendTab(title, href, id);
            } catch (err) {
                location.href = href;
            }
        }
    },
    /**
     * 上传
     * the upload.run most code is from http://layuimini.99php.cn/,
     * @link https://layui.dev/docs/2/upload/
     */
    upload: {
        /**
         * 添加 圖片
         * @param {string} url 圖片地址
         * @param {string} name 操作來源 upload, picker
         */
        after: function (url, name) {
            console.log('todo upload.after:', url, name)
        },
        /**
         * 移除圖片
         * @param url
         */
        remove: function (url) {
        },
        /**
         * 執行上傳
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
                        uploadNumber = jThis.attr('data-upload-number') || 'one', // 可选择图片数量
                        uploadSeparator = jThis.attr('data-upload-separator') || '|', // 分割符
                        uploadAccept = jThis.attr('data-upload-accept') || 'file',
                        uploadAcceptMime = jThis.attr('data-upload-mimetype') || '';
                    const elem = "input[name='" + uploadName + "']",
                        multiple = uploadNumber !== 'one',
                        uploadElem = this;
                    const headers = admin.config.ajax.headers();

                    // 监听上传事件
                    layui.upload.render({
                        elem: this,
                        url: admin.config.url.imageSave,
                        exts: uploadExts,
                        accept: uploadAccept,
                        acceptMime: uploadAcceptMime,
                        multiple: multiple,
                        headers: headers,
                        before: function (obj) {
                            layui.layer.load();
                        },
                        done: function (res) {
                            layui.layer.closeAll();
                            if (res.code === 0) {
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
                                admin.upload.after(url, 'upload');
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
                        admin.upload.after($(this).val(), 'blur');
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
                                liHtml += `<li>
<a><img src="${v}"></a>
<small class="uploads-delete-tip bg-red badge" data-upload-delete="${uploadName}" data-upload-url="${v}" data-upload-separator="${uploadSeparator}">×</small>
</li>`;
                            });
                            parant.after('<ul id="bing-' + uploadName + '" class="layui-input-block layuimini-upload-show">\n' + liHtml + '</ul>');
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
                        admin.upload.remove(url);
                    });
                    return false;
                });
            }
// 图片选择
            if (uploadSelectList.length > 0) {
                layui.jquery.each(uploadSelectList, function (i, v) {
                    const uploadName = $(this).attr('data-upload-select'),
                        uploadNumber = $(this).attr('data-upload-number') || 'one',
                        uploadSeparator = $(this).attr('data-upload-separator') || '|';

                    const selectCheck = uploadNumber === 'one' ? 'radio' : 'checkbox',
                        inputElem = $("input[name='" + uploadName + "']"),
                        uploadElem = $(this).attr('id');

                    $('#' + uploadElem).off('click').on('click', function () {
                        admin.iframe.open(
                            admin.config.url.imageList + '?type=' + selectCheck, {
                                title: '图片选择',
                                end: function () {
                                    admin.storage.getArray('images', images => {
                                        const url = images.join(uploadSeparator);
                                        inputElem.val(url);
                                        inputElem.trigger("input");
                                        admin.layer.success('选择成功');
                                        admin.upload.after(url, 'picker');
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
                        admin.upload.after(value, 'edit');
                        layer.close(index); // 关闭层
                    });
                })
            }
            return this;
        }
    },
    /**
     * 表格数据
     * @link https://layui.dev/docs/2/table/
     */
    table: {
        /**
         * table 的配置信息
         * @private
         */
        _config: {
            id: 'table',
            url: '',
            autoApi: true,
            tableId: null, // 在 table.render 时会自动赋值
            key: 'id',
            rowAction: function () {
            }
        },
        /**
         * 获取配置信息
         * @param config {Object} 配置值
         * @param key {string}
         * @param {any} defV 默认值
         */
        getConfig: function (config, key, defV = null) {
            if (!admin.util.isEmpty(config) && !admin.util.isEmpty(config[key])) {
                return config[key];
            }
            return this._config[key] || defV;
        },
        /**
         * 初始化 id: 表格 ID，url 请求接口, rowAction:修改行数据时回调函数
         * @param {{id?:string, url?:string, rowAction?:function}} [config]
         * @return this;
         */
        with: function (config = {}) {
            Object.assign(this._config, {url: admin.ajax.apiURL()}, config)
            // console.log(this._config)
            return this;
        },
        getTableId: function () {
            return this._config.tableId;
        },
        reloadData: function () {
            this._config.tableId.reloadData();
        },
        reloadWhenIframeRefresh: function () {
            admin.iframe.hasRefresh(() => {
                admin.table.reloadData()
            })
        },
        /**
         * 表格渲染<pre>
         * lineStyle: 'height: 95px;' 多行樣式
         * </pre>
         * @param {{toolbar?:string,url?:string,cols:Array,page?:boolean,lineStyle?:string}} options 表格 render 时配置信息
         * @param {{search?:boolean}} [additions] 其它配置信息
         * @return this
         */
        render: function (options, additions = {}) {
            const tableId = this._config.id;

            const config = Object.assign({
                elem: '#' + tableId,
                url: this._config.url || admin.ajax.apiURL(),
                defaultToolbar: ['filter', {
                    title: '搜索',
                    layEvent: 'search',
                    icon: 'layui-icon-search'
                }],
                page: true,
                parseData: function (res) { // res 即为原始返回的数据
                    return {
                        "code": res.code, // 解析接口状态
                        "msg": res.message, // 解析提示文本
                        "count": res.data.count, // 解析数据长度
                        "data": res.data.rows // 解析数据列表
                    };
                },
            }, options)
            if (config.page && admin.util.isEmpty(config['limit'])) {
                config['limit'] = 15;
                config['limits'] = [1, 15, 30, 50];
            }
            const adds = Object.assign({
                search: true,
            }, additions)
            const currentTable = layui.table.render(config);

            // 搜索框，需要指定的格式
            const tableSearchElem = $('#' + tableId + '-search');
            if (adds.search) {
                if (tableSearchElem.length === 1) {
                    // 重置按钮
                    const resetElem = tableSearchElem.find('button[type=reset]');
                    if (resetElem.length === 1) {
                        resetElem.bind('click', function () {
                            currentTable.reloadData({
                                where: {reset: 1},
                                page: {curr: 1}
                            })
                        })
                    } else {
                        console.log('没有找到重置按钮 <button type="reset" class="layui-btn layui-btn-primary">重置</button>')
                    }
                    // 搜索按钮
                    const submitElem = tableSearchElem.find('a[lay-submit]');
                    if (submitElem.length === 1) {
                        submitElem.bind('click', function (e) {
                            e.stopPropagation();
                            e.preventDefault();
                            // <form className="layui-form layui-form-pane form-search"  lay-filter="form-search"></form>
                            const data = form.val('form-search');
                            // console.log(data);
                            currentTable.reloadData({
                                where: data,
                                page: {curr: 1}
                            })
                        })
                    } else {
                        console.log('没有找到提交按钮 <a class="layui-btn layui-btn-normal" lay-submit>搜索</a>')
                    }

                    // 显示/隐藏搜索框
                    table.on('toolbar(' + tableId + ')', function (obj) {
                        switch (obj.event) {
                            case 'search':
                                if (tableSearchElem.hasClass('layui-hide')) {
                                    tableSearchElem.removeClass('layui-hide')
                                } else {
                                    tableSearchElem.addClass('layui-hide');
                                }
                                // tableSearchElem.toggle();
                                break;
                        }
                    });
                } else {
                    console.log('开启了条件搜索，但没有找到 <fieldset id="' + tableId + '-search">')
                }
            }
            // 工具栏事件
            layui.util.on('lay-on', {
                // 刷新按钮
                refresh: function () {
                    // admin.layer.load();
                    currentTable.reloadData()
                },
            })
            this._config.tableId = currentTable;
            return this;
        },
        /**
         * 监听 lay-on 事件，如 工具栏的 batchDelete/create
         * @param {{url?:string}} [config] 配置信息
         * @return this
         */
        addLayOnActions: function (config = {}) {
            const tableId = this._config.id;
            const url = config && config.url ? config.url : this._config.url;
            layui.util.on('lay-on', {
                // 批量删除（工具栏）
                batchDelete: function () {
                    const rows = table.checkStatus(tableId);
                    if (rows.data.length < 1) {
                        admin.layer.error('没有选中任何记录!')
                    } else {
                        const ids = rows.data.map(r => r.id);
                        admin.layer.confirm('确定要删除这些记录吗？', function () {
                            admin.ajax.postLimit({
                                url: '/api' + url + '/delete', data: {id: ids.join(',')},
                            }, function () {
                                layui.table.reload(tableId);
                                admin.table._config.rowAction('batchDelete', ids.join(','));
                            })
                        })
                    }
                },
                // 添加记录（工具栏），如果需要全屏，则添加 data-full
                create: function () {
                    admin.iframe.open(url + '/add', {
                        title: '添加记录',
                        full: !$(this).attr('data-auto'),
                        end: function () {
                            admin.iframe.hasRefresh(() => {
                                layui.table.reload(tableId);
                                admin.table._config.rowAction('add');
                            })
                        }
                    })
                },
            })
            return this;
        },
        /**
         * row-action 中 lay-event 自定义事件
         * @param action {Function} 第一个参数事件名称，第二个参数为行数据
         */
        events: function (action) {
            const tableId = this._config.id;
            layui.table.on('tool(' + tableId + ')', function (obj) {
                action(obj.event, obj.data);
            });
        },
        /**
         * 监听行操作事件 lay-event，通常绑定在 table.cols.toolbar 上
         * @param {{url?:string, events?:Function, key?:string}} [config] 回调函数，obj.event 是事件名称, obj.data 是当前行数据
         * @return this
         */
        addLayEventActions: function (config = {}) {
            const tableId = this._config.id;
            const url = this.getConfig(config, 'url');
            const key = this.getConfig(config, 'key', 'id');

            layui.table.on('tool(' + tableId + ')', function (obj) {
                const keyV = obj.data[key];
                switch (obj.event) {
                    case 'edit':
                        if (admin.table._config.rowAction('edit', obj.data) === false) {
                            return;
                        }
                        admin.iframe.open(url + '/edit?' + key + '=' + keyV, {
                            full: true,
                            title: '编辑记录', end: function () {
                                admin.iframe.hasRefresh(() => {
                                    layui.table.reloadData(tableId);
                                })
                            }
                        });
                        return;
                    case 'delete':
                    case 'remove':
                        if (admin.table._config.rowAction('delete', obj.data) === false) {
                            return;
                        }
                        admin.layer.confirm('确定要删除当前记录吗！', function () {
                            admin.ajax.postLimit({
                                    url: '/api' + url + '/delete', data: {
                                        [key]: keyV,
                                    },
                                },
                                function () {
                                    admin.table.removeWith(s => s[key] === keyV)
                                }
                            )
                        })
                        return;
                    default:
                        if (typeof config['events'] === 'function') {
                            config.events(obj);
                        } else {
                            console.warn('没有添加事件处理函数:', obj.event)
                        }
                }
            })
            return this;
        },

        icon: function (data) {
            const v = data[this.field];
            return `<i class="${v}"></i>`
        },
        image: function (data, useV = false) {
            const option = {
                imageWidth: this.imageWidth || 200,
                imageHeight: this.imageHeight || 40,
                imageSplit: this.imageSplit || '|',
                imageJoin: this.imageJoin || '<br>',
                title: this.title || this.field,
                field: this.field,
                value: useV === true ? data : data[this.field],
            }
            if (option.value === undefined || option.value === null) {
                return '<img style="max-width: ' + option.imageWidth + 'px; max-height: ' + option.imageHeight + 'px;" src="' + option.value + '" data-image="' + option.title + '">';
            } else {
                const values = option.value.split(option.imageSplit),
                    valuesHtml = [];
                values.forEach((value, index) => {
                    valuesHtml.push('<img onclick="admin.layer.image(\'' + value + '\')" style="max-width: ' + option.imageWidth + 'px; max-height: ' + option.imageHeight + 'px;" src="' + value + '">');
                });
                return valuesHtml.join(option.imageJoin);
            }
        },
        /**
         * switch 模板
         * @param data {Object} 配置信息
         * @return string
         */
        switch: function (data) {
            const option = {
                field: this.field,
                value: data[this.field],
                selectList: this.selectList || {0: '禁用', 1: '启用'},
                tips: this.tips || '开|关',
                filter: this.filter || this.field
            }
            const key = admin.table._config.key;
            if (admin.util.isEmpty(data[key])) {
                return '';
            }
            const checked = [1, "1"].includes(option.value) ? 'checked' : '';
            // console.log(data,option,checked)
            return `<input type="checkbox" name="${option.field}" value="${data[key]}"
lay-skin="switch" lay-text="${option.tips}" lay-filter="${option.filter}" ${checked}>`;
        },
        /**
         * 時間格式化
         * @param {string|number} data 對象或者是值
         * @return {string}
         */
        humanTime: function (data) {
            const datat = typeof data;
            const v = datat === 'string' || datat === 'number' ? data : data[this.field];
            if (admin.util.isEmpty(v)) {
                return '';
            }
            const date = new Date(v * 1000);  // 参数需要毫秒数，所以这里将秒数乘于 1000
            return ([
                date.getFullYear(),
                ('' + (date.getMonth() + 1)).padStart(2, '0'),
                ('' + date.getDate()).padStart(2, '0')
            ].join('-') + ' ' + [
                ('' + date.getHours()).padStart(2, '0'),
                ('' + date.getMinutes()).padStart(2, '0'),
                ('' + date.getSeconds()).padStart(2, '0')
            ].join(':')).replace(' 00:00:00', '')
        },
        humanDate: function (data, useV = false) {
            const v = useV === true ? data : data[this.field];
            if (admin.util.isEmpty(v)) {
                return '';
            }
            const date = new Date(v * 1000);  // 参数需要毫秒数，所以这里将秒数乘于 1000
            return [
                date.getFullYear(),
                ('' + (date.getMonth() + 1)).padStart(2, '0'),
                ('' + date.getDate()).padStart(2, '0')
            ].join('-')
        },
        objPath: function (data) {
            const keys = this.field.split('.')
            let currentObj = data;
            for (let i = 0; i < keys.length; i++) {
                if (currentObj.hasOwnProperty(keys[i])) {
                    currentObj = currentObj[keys[i]]
                } else {
                    currentObj = null;
                    break;
                }
            }
            return `<div>${currentObj}</div>`;
        },
        /**
         * 监听 switch
         * @param field {string}
         * @param action {Function} 回调 <pre>{
         *     value:当前值(ID), checked: 选中状态, obj: $对象用于获取其它属性
         * }</pre>
         * @link https://layui.dev/docs/2/form/checkbox.html#on
         */
        listenSwitch: function (field, action) {
            layui.form.on('switch(' + field + ')', function (data) {
                const elem = data.elem; // 获得 checkbox 原始 DOM 对象
                const othis = data.othis; // 获得 checkbox 元素被替换后的 jQuery 对象
                action({value: elem.value, checked: elem.checked, obj: othis})
            })
        },
        /**
         * 提交 switch
         * @param {Array} fields 字段名称，默认为 ['status']
         * @param {{url:string}} [config] 配置信息
         * @return this
         */
        addPostSwitch: function (fields = ['status'], config = {}) {
            const url = this.getConfig(config, 'url');
            const key = this.getConfig(config, 'key', 'id');

            fields.forEach(field => {
                if (admin.util.isEmpty(field)) {
                    console.warn('listenSwitch empty field')
                    return;
                }
                admin.table.listenSwitch(field, function (data) {
                    const postData = {
                        [key]: data.value, field, value: data.checked ? 1 : 2
                    }
                    admin.ajax.post({
                        url: url + '/modify',
                        data: postData,
                    }, function () {
                        admin.table._config.rowAction('modify', postData);
                    })
                })
            })


            return this;
        },
        /**
         * 监听编辑单元
         * @param {string} tableId 表格 ID
         * @param {Function} callback 回调 <pre> {
         *  field: 修改的字段, value: 修改后的值, oldValue: 修改前的值,data: 所在行的数据
         * }</pre>
         */
        listenEditText: function (tableId, callback) {
            table.on('edit(' + tableId + ')', callback)
        },
        /**
         * 提交 编辑单元 EditText
         * @param {{url?:string,ok?:Function}} [config] 配置信息
         * @return this
         */
        addPostEditText: function (config) {
            const tableId = this._config.id;
            const url = config && config.url ? config.url : this._config.url;

            admin.table.listenEditText(tableId, function (obj) {
                const postData = {
                    id: obj.data.id, field: obj.field, value: obj.value,
                }
                admin.ajax.post({
                    url: url + '/modify',
                    data: postData,
                }, function () {
                    if (config && typeof config.ok === "function") {
                        config.ok(postData);
                    }
                    admin.table._config.rowAction('modify', postData);
                })
            })
            return this;
        },
        /**
         * 移除指定的数据，并重载数据
         * @param predicate
         * @return this
         */
        removeWith: function (predicate) {
            const tableId = this._config.id;
            const rows = layui.table.cache[tableId];
            const index = rows.findIndex(predicate);
            if (index > -1) {
                rows.splice(index, 1);
                layui.table.renderData(tableId);
            } else {
                admin.layer.error('没有找到需要删除的数据项')
            }
            return this;
        }
    },
    // https://layui.dev/docs/2/treeTable
    treeTable: {
        render: function (config) {
            return layui.treeTable.render(Object.assign({
                elem: '#table',
                url: admin.ajax.apiURL(),
                toolbar: '#toolbar',
                parseData: function (res) { // res 即为原始返回的数据
                    return {
                        "code": res.code, // 解析接口状态
                        "msg": res.message, // 解析提示文本
                        "count": res.data.count, // 解析数据长度
                        "data": res.data.rows // 解析数据列表
                    };
                },
            }, config))
        }
    },
    /**
     * 日期
     * @link https://layui.dev/docs/2/laydate
     */
    date: {
        /**
         * 日期绑定
         * <input name="create_time" value="" id="create_time" class="layui-input">
         * @param {string} id ID 名称
         * @param {boolean} range 是否为范围，默认为 true
         */
        renderDate: function (id, range = true) {
            layui.laydate.render({
                elem: '#' + id,
                type: 'date',
                range,
            })
        },
        renderDatetime: function (id) {
            layui.laydate.render({
                elem: '#' + id,
                type: 'datetime'
            });
        }
    },
    /**
     * localStorage 缓存
     */
    storage: {
        read: function (name) {
            return localStorage.getItem(name);
        },
        save: function (name, data) {
            localStorage.setItem(name, typeof data === 'object' ?
                JSON.stringify(data) : data)
        },
        getArray: function (name, success, remove = true) {
            const data = localStorage.getItem(name);
            if (remove) {
                localStorage.removeItem(name);
            }
            // console.log('rows',name, data)
            if (data && data.length > 0) {
                const rows = JSON.parse(data);
                if (rows && rows.length > 0) {
                    success(rows);
                }
            }
        },
        getFirst: function (name, success, remove = true) {
            this.getArray(name, rows => {
                success(rows[0])
            }, remove);
        },
        getParse: function (name, success, remove = true) {
            const data = localStorage.getItem(name);
            if (remove) {
                localStorage.removeItem(name);
            }
            const rst = data && data.length > 0 ? JSON.parse(data) : null;
            if (rst) {
                success(rst);
            }
        }
    },
    /**
     * layui 缓存
     * @link https://layui.dev/docs/2/base.html#data
     */
    cache: {
        table: 'phax',
        /**
         * 读取缓存
         * @param {string} key
         * @param {any} defV 默认值
         */
        read: function (key, defV) {
            const local = layui.data(this.table);
            const v = local[key];
            if (admin.util.isEmpty(v)) {
                return defV;
            }
            return JSON.parse(v);
        },
        save: function (key, data) {
            layui.data(this.table, {
                key: key, value: JSON.stringify(data),
            })
        },
        remove: function (key) {
            layui.data(this.table, {
                key: key, remove: true,
            })
        },
        clear: function () {
            localStorage.clear();
        }
    },
    /**
     * 模板引擎 https://layui.dev/docs/2/laytpl/#test
     * 分页 https://layui.dev/docs/2/laypage/
     */
    pagination: {
        _cache: {},
        /**
         * 配置
         * @param name {string} 名称
         * @param options {{id?:string, groups?:number, tpl?:string,view?:string,url:string}}
         */
        with: function (name, options = {}) {
            const tplName = options.tpl || name + 'Tpl';
            const tplElem = document.getElementById(tplName);
            if (tplElem == null) {
                console.warn('could not find tpl for pagination:', tplName)
                return false;
            }
            const viewName = options.view || name + 'View';
            const viewElem = document.getElementById(viewName);
            if (viewElem == null) {
                console.warn('could not find view for pagination:', viewName)
                return false;
            }
            this._cache[name] = Object.assign({
                id: 'pag', // 绑定分页 <div class="text-center" id="pag"></div>
                count: 0, curr: 1, rows: [], hasLoad: false,
                groups: options.groups || 1, // 只显示1页
                tplElem: layui.laytpl(tplElem.innerHTML),
                viewElem: viewElem,
            }, options);
            return true;
        },
        getOption: function (name) {
            if (admin.util.isEmpty(this._cache[name])) {
                throw new Error('pagination option not find:' + name);
            }
            return this._cache[name];
        },
        /**
         * 请求
         * @param name {string} 名称
         */
        request: function (name) {
            const cd = this.getOption(name);
            admin.ajax.get({url: cd.url}, res => {
                cd.hasLoad = true;
                cd.count = res.count;
                cd.rows = res.data;
                this.render(name)
            })
        },
        /**
         * 渲染分页的模板
         * @param name {string}
         */
        render: function (name) {
            const cd = this.getOption(name)
            layui.laypage.render({
                elem: cd.id,
                count: cd.count, curr: cd.curr, groups: cd.groups,
                first: false, last: false, limit: 15,
                layout: cd.layout || ['prev', 'page', 'next', 'count'],
                jump: function (obj, firstRun) {
                    // console.log(name, firstRun)
                    if (firstRun) {

                    } else {
                        cd.url = admin.util.appendOrReplaceQueryParam(cd.url, 'page', obj.curr)
                        cd.url = admin.util.appendOrReplaceQueryParam(cd.url, 'limit', obj.limit)

                        admin.ajax.get({url: cd.url}, res => {
                            cd.count = res.count;
                            cd.rows = res.data;
                        })
                    }

                    cd.tplElem.render(cd, function (content) {
                        cd.viewElem.innerHTML = content;
                    })
                }
            })
        },
    },
};