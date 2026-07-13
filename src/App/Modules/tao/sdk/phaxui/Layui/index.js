/**
 * @link https://layui.dev/docs/2
 */
const admin = {
    /**
     * 初始化配置
     */
    config: {
        debug: false,
        ajax: {
            csrf: false, // 是否启用 csrf-token
            headers: function () {
                if (this.csrf) {
                    if (!admin.util.isEmpty(window.CONFIG) && !admin.util.isEmpty(window.CONFIG.CSRF_TOKEN)) {
                        return {'X-CSRF-TOKEN': window.CONFIG.CSRF_TOKEN}
                    }
                }
                return {};
            },
            /**
             * 刷新 CSRF-TOKEN
             * @param {string} method
             * @param {string} url
             */
            refreshHeaders: function (method, url) {
                if (this.csrf && ['post', 'put', 'delete'].includes(method.toLowerCase())) {
                    console.log('try to refresh csrf-token');
                    // @todo 刷新 csrf-token
                }
            }
        },
    },
    /**
     * 当前是否开启调试模式
     * @returns {boolean}
     */
    debug: function () {
        return admin.config.debug || false;
    },
    /**
     * 工具类
     */
    util: {
        /**
         * 防抖代理(在事件停止触发后延迟一段时间执行)，函数按指定毫秒延时执行 <br>
         * 适用于输入框搜索、窗口调整等场景。只有在事件在指定时间内没有再次触发，事件处理函数才会执行
         * @example
         * admin.table.with({url: prefix})
         *    .render({
         *        limit: 1000000, limits: [1000000],
         *        defaultToolbar: [],
         *        cols: cols,
         *    },{
         *        success: function (res) {
         *            allData = res.data.rows || [];
         *            console.log('请求完成', allData.length)
         *            if (allData.length > 0 && $('#filterBar').is(':hidden')) {
         *                $('#filterBar').show();
         *                layui.form.render('select');
         *            }
         *        }
         *    });
         * // 1. 防抖函数只初始化一次，放在外层，不要写在 doFilter 内部
         * const debounceRenderTable = admin.util.debounce(function (filteredList) {
         *    console.log(allData.length)
         *    const tableId = admin.table.id(); // 替换成你table.render配置的id
         *    // 直接覆盖表格缓存实现数据替换
         *    layui.table.cache[tableId] = filteredList;
         *    // 重绘表格
         *    layui.table.renderData(tableId);
         * }, 500);
         *
         * // 过滤主函数
         * function doFilter() {
         *    // allData 是原始完整数据源，全程不修改、不覆盖
         *    var keyword = $('#filterKeyword').val().trim().toLowerCase();
         *    var material = $('#filterMaterial').val();
         *
         *    // 过滤主体数据
         *    var filtered = allData.filter(function (d) {
         *        if (keyword && (d.customerName || '').toLowerCase().indexOf(keyword) < 0) return false;
         *        if (material && parseInt(d.material) !== parseInt(material)) return false;
         *        return true;
         *    });
         *
         *    // 调用防抖渲染，把过滤好的数据传进去
         *    debounceRenderTable(filtered);
         * }
         *
         * // 绑定输入事件（输入时触发过滤）
         * $('#filterKeyword, #filterMaterial').on('input change', function () {
         *    doFilter();
         * });
         * @param fn
         * @param wait
         */
        debounce: function (fn, wait) {
            return layui.debounce(fn, wait);
        },
        /**
         * 节流代理，限制函数在指定毫秒内不重复执行
         * 在一定时间内多次触发事件时，只执行一次事件处理函数，适用于滚动事件、鼠标移动等高频触发场景。节流保证固定频率执行
         * @param fn
         * @param wait
         */
        throttle: function (fn, wait) {
            return layui.throttle(fn, wait);
        },
        /**
         * 判断值是否为空（0/'null'/undefined/空对象/空数组 均视为空）
         * @param {*} data 待检查的值
         * @returns {boolean}
         * @example
         * admin.util.isEmpty(null)         // true
         * admin.util.isEmpty('')            // true
         * admin.util.isEmpty('null')        // true
         * admin.util.isEmpty({})            // true
         * admin.util.isEmpty([])            // true
         * admin.util.isEmpty(0)             // true
         * admin.util.isEmpty('abc')         // false
         * admin.util.isEmpty({a:1})         // false
         */
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
        /**
         * 拼接成 URL 请求参数（自动 encode）
         * @link https://layui.dev/docs/2/base.html#url
         * @param {string} url
         * @param {Object} dict 参数键值对
         * @returns {string} 编码后的 query string，如 "name=abc&age=18"
         */
        concatQuery: function (url, dict) {
            if (admin.util.isEmpty(dict)) {
                return url;
            }
            var d = [];
            for (var key in dict) {
                if (admin.util.isEmpty(dict[key])) {
                    continue;
                }
                if (Object.prototype.hasOwnProperty.call(dict, key)) {
                    d.push(encodeURIComponent(key) + '=' + encodeURIComponent(dict[key]));
                }
            }
            const separator = url.includes('?') ? '&' : '?';
            return url + separator + d.join('&');
        },
        /**
         * 过滤空值
         * @param {Object} dict
         * @return {Map<K, V>}
         */
        filter: function (dict) {
            const d = {};
            for (let k in dict) {
                if (!admin.util.isEmpty(dict[k])) {
                    d[k] = dict[k];
                }
            }
            return d;
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
         * layui.util.on({
         *      e1:function(othis){
         *          console.log(othis.html());
         *          console.log(this.getAttribute('data-kind'));
         *      }
         * });
         */
        layOn: function (binds) {
            layui.util.on('lay-on', binds);
        },
        /**
         * 从 obj 中挑选出需要的字段
         * @param {Object} obj 源对象
         * @param {...string} props 要提取的字段名
         * @returns {Object} 包含所选字段的新对象
         * @example admin.util.pick({a:1, b:2, c:3}, 'a', 'c') // {a:1, c:3}
         */
        pick: function (obj, ...props) {
            return Object.assign({}, ...props.map(function (prop) {
                return {[prop]: obj[prop]};
            }));
        },
        /**
         * PHP 时间戳（秒）格式化为日期字符串
         * @param {number} phpTimestamp PHP 时间戳（秒）
         * @param {string} [format] 格式，默认 'yyyy-MM-dd'，支持 yyyy MM dd HH mm ss
         * @returns {string}
         * @example admin.util.toDateString(1684723200) // "2025-05-22"
         */
        toDateString: function (phpTimestamp, format = 'yyyy-MM-dd') {
            if (phpTimestamp < 1) {
                return '';
            }
            return layui.util.toDateString(phpTimestamp * 1000, format)
        },
        /**
         * 金额友好格式化（千分位 + 可选隐藏 .00）
         * @param {number | string} amount - 原始金额（支持数字/字符串）
         * @param {boolean} showZeroDecimal - 是否显示末尾 .00，默认 false（隐藏）
         * @returns {string} 格式化后的金额
         */
        formatMoney: function (amount, showZeroDecimal = true) {
            if (amount === null || amount == undefined || amount == '' || amount == 0 || amount == '0') {
                return showZeroDecimal ? '0.00' : '0'
            }
            // 第一步：转数字并容错处理
            const num = parseFloat(amount)
            if (isNaN(num)) return '0.00'

            // 第二步：固定保留两位小数
            let fixed = num.toFixed(2)

            // 第三步：添加千分位逗号（正则匹配）
            let formatted = fixed.replace(/\B(?=(\d{3})+(?!\d))/g, ',')

            // 第四步：如果不需要显示 .00 且小数部分是 00，则去掉
            if (!showZeroDecimal && formatted.endsWith('.00')) {
                formatted = formatted.slice(0, -3)
            }
            return formatted
        },
        /**
         * 時間格式化
         * @param {string|number} data 對象或者是值
         * @return {string}
         */
        humanTime: function (data) {
            if (admin.util.isEmpty(data)) {
                return '';
            }
            const date = new Date(data * 1000);  // 参数需要毫秒数，所以这里将秒数乘于 1000
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
        humanDate: function (data) {
            if (admin.util.isEmpty(data)) {
                return '';
            }
            const date = new Date(data * 1000);  // 参数需要毫秒数，所以这里将秒数乘于 1000
            return [
                date.getFullYear(),
                ('' + (date.getMonth() + 1)).padStart(2, '0'),
                ('' + date.getDate()).padStart(2, '0')
            ].join('-')
        },
    },

    /**
     * 消息提示
     * @link https://layui.dev/docs/2/layer/#options.callback
     */
    layer: {
        image: function (src, title = '图片展示') {
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
                // time: 3000,
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
         * 加载提示，并在指定秒数后自动关闭
         * @param {number} [seconds] 秒数，默认 2
         */
        load: function (seconds = 2) {
            const index = layer.load(2, {time: seconds * 1000});
            // 使用 setTimeout 延迟关闭，使 loading 可见
            setTimeout(function () {
                layer.close(index);
            }, seconds * 1000);
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
         * 用于 layer.msg/load 的 end 回调，当不传 callback 时自动关闭层
         * @param {function} [callback]
         * @returns {function}
         * @private
         */
        _createCallback_: function (callback) {
            if (typeof callback === "function") {
                return callback;
            }
            return function (index) {
                if (!admin.util.isEmpty(index)) {
                    admin.layer.close(index);
                }
            };
        },
        /**
         * 弹出一个单选选择框
         * @param {string} title 标题
         * @param {Object} kvMap 选项
         * @param {function(value:String, index:number)} success `(value, index)` 选项选中后的回调，接收选中的值，注意关闭层
         * @param {String} appendContent 其它的 HTML 内容
         */
        radiosDialog: function (title, kvMap, success, appendContent = '') {
            var content = '';
            $.each(kvMap, function (val, label) {
                content += '<input type="radio" name="toValue" value="' + val + '" title="' + label + '">';
            });

            layer.open({
                type: 1,
                title: title,
                area: ['400px', '300px'],
                content: '<div style="padding: 30px 20px;">'
                    + '<div class="layui-form" style="text-align: left;">'
                    + content
                    + '</div>'
                    + '</div>' + appendContent,
                success: function (layero) {
                    layui.form.render('radio');
                },
                btn: ['确定', '取消'],
                yes: function (index) {
                    var value = $('input[name="toValue"]:checked').val();
                    if (!value) {
                        layui.layer.msg('请选择' + title, {icon: 2});
                        return;
                    }
                    success(value, index);
                }
            });
        },
        /**
         * 弹出一个输入框
         * @param {String} title 标题
         * @param {String} placeholder 输入提示
         * @param {function(value:String, index:number)} success 回调函数
         * @param {function(value:String):string|null} [validate] 验证函数，返回 string 表示错误信息，其它表示验证通过
         */
        input: function (title, placeholder, success, validate = null) {
            layer.prompt({
                title,
                formType: 0,
                area: ['300px', '160px'],
                placeholder
            }, function (value, index) {
                if (typeof validate === 'function') {
                    const result = validate(value);
                    if (typeof result === 'string') {
                        layui.layer.msg(result, {icon: 2});
                        return;
                    }
                }
                success(value, index);
            });
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
         * @param {function} [success] 返回成功回调
         */
        get: function (option, success) {
            this.ajax('get', option, success);
        },
        /**
         * 请求方法
         * @param option {{url?:string,data:Object}}
         * @param {function} [success] 返回成功回调
         */
        post: function (option, success) {
            this.ajax('post', option, success)
        },
        // layui.debounce(fn, wait) 防抖，layui.throttle(fn, wait) 节流
        /**
         * 防抖、节流请求
         * @param option {{url?:string,data:Object}}
         * @param {function} [success] 返回业务逻辑成功回调
         * @param {function} [failed] 返回业务逻辑错误回调
         * @param {function} [complete] 完成请求回调
         */
        postLimit: function (option, success, failed, complete) {
            let self = this;
            if (!this._postLimitDebounced) {
                // 用闭包保存 option/ok/no/complete/ex，让 debounce 只用最新参数
                this._postLimitDebounced = layui.debounce(function () {
                    if (self._isPosting) {
                        return;
                    }
                    self._isPosting = true;
                    let args = self._postLimitArgs;
                    self.ajax('post', args.option, args.success, args.failed, function (data) {
                        self._isPosting = false;
                        if (typeof args.complete == 'function') {
                            args.complete(data);
                        }
                    });
                }, 300);
            }
            // 保存最新参数
            this._postLimitArgs = {option: option, success: success, failed: failed, complete: complete};
            this._postLimitDebounced();
        },
        /**
         * 通用 Ajax 请求封装
         * @param {string} type 请求请求方式：get / post / put / delete，不区分大小写
         * @param {Object} option 请求配置对象
         * @param {string} [option.url] 接口请求地址，不传时自动基于当前页面 location 拼接接口路径
         * @param {Object} option.data 接口请求提交参数
         * @param {Object} [option.headers={}] 自定义请求头，会与 admin.config.ajax.headers() 进行合并
         * @param {string} [option.dataType='json'] 服务端响应数据解析类型，默认 json
         * @param {string} [option.contentType='application/x-www-form-urlencoded; charset=UTF-8'] 请求头 Content-Type
         * @param {number} [option.timeout=60000] 请求超时时间，单位毫秒，默认60秒
         * @param {string} [option.statusName='code'] 后端返回体中代表业务状态码的字段名，默认 code
         * @param {number} [option.statusCode=0] 判定业务请求成功的状态码，默认 0
         * @param {Function|string|null} success 业务成功回调；传函数则执行回调，传字符串自动弹出成功提示文案，传 null 不处理
         * @param {Function|string|null} error 业务失败回调；传函数执行失败回调，传字符串自动弹出错误提示文案，传 null 不处理
         * @param {Function|null} complete 请求完成统一回调（成功/失败都会执行），回调参数为接口完整返回数据 data
         */
        ajax: function (type, option, success, error = null, complete = null) {
            option = Object.assign({
                url: '',
                data: {},
                dataType: 'json',
                contentType: "application/x-www-form-urlencoded; charset=UTF-8",
                timeout: 60000,
                statusName: 'code',
                statusCode: 0,
            }, option)
            if (option.url === '' || option.url == null || option.url == undefined) {
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
                headers: Object.assign({}, admin.config.ajax.headers(), option.headers || {}),
                success: function (res) {
                    if ([0, 200].includes(res[option.statusName])) { // 成功
                        if (typeof success === 'string') { // 字符串，直接显示成功信息
                            admin.layer.success(success);
                        } else if (res.msg) {
                            if (typeof success == 'function') { // 回调函数
                                admin.layer.success(res.msg, () => {
                                    success(res);
                                });
                                return;
                            }
                            admin.layer.success(res.msg);
                        }
                        if (typeof success == 'function') {
                            success(res);
                        }
                    } else { // 业务逻辑失败
                        admin.layer.alert(typeof error == 'string' ? error : res.msg, function () {
                            admin.config.ajax.refreshHeaders(option.type, option.url);
                            typeof error == 'function' && error(res);
                        }, {title: '出错啦', shadeClose: true, icon: 2, maxWidth: 360});
                    }
                },
                error: function (xhr, textstatus, thrown) {
                    admin.config.ajax.refreshHeaders(option.type, option.url);
                    admin.layer.error('Status:' + xhr.status + '，' + xhr.statusText + '，请稍后再试！');
                },
                complete: function (xhr) {
                    admin.layer.close(loadIndex);
                    let data = null;
                    try {
                        data = JSON.parse(xhr.responseText).data;
                    } catch (e) {
                        console.error('响应结果不符合规范:', option.url);
                    }
                    if (typeof complete === 'function') {
                        complete(data);
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
         * lay-filter 事件
         * @example
         * form.on('radio(filter)', callback);
         * // 单选框事件 ,filter 为单选框元素对应的 lay-filter 属性值
         * form.on('select', function(data){console.log(data);});
         * // 指向所有 select 组件的选择事件
         * form.on('select(test)', function(data){console.log(data);});
         * // 指向元素为 `<select lay-filter="test"></select>` 的选择事件
         *
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
         * @param {function|string} success
         * @param {function} [postDataCallback] 如果返回 false 则取消表单提交；否则返回提交的数据
         * @param {{url:string|Function}} [options]
         * @returns {boolean}
         */
        submitFirst: function (success, postDataCallback, options = {url: ''}) {
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
                    let postData = data.field; // 请求的字段
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
                    admin.ajax.postLimit({url: url, data: postData}, success);
                    return false;
                })

            }
        },
        /**
         * 修改表单对应名称组件的值
         * @param {Object} kvs 修改成新的值 {name:'aaa'}
         */
        updateValueByName: function (kvs) {
            for (const name in kvs) {
                document.getElementsByName(name)[0].value = kvs[name];
            }
        },
        /**
         * 表单赋值
         * @link https://layui.dev/docs/2/form/#val
         * @param {string} layFilterName 添加在 form 上的 lay-filter 如 `<form class="layui-form" action="" lay-filter="demo-val-filter">`
         * @param {Object} values 赋值
         */
        patch: function (layFilterName, values) {
            layui.form.val(layFilterName, values);
        },
        /**
         * 表单取值
         * @param {string} layFilterName 添加在 form 上的 lay-filter 如 `<form class="layui-form" action="" lay-filter="demo-val-filter">`
         * @returns {*}
         */
        value: function (layFilterName) {
            return layui.form.val(layFilterName);
        },
        /**
         * 监听 select
         * @link https://layui.dev/docs/2/form/select.html#lay-append
         * @param {string} layFilterName
         * @param {Function} callback `var elem = data.elem; // 获得 select 原始 DOM 对象
         *     var value = data.value; // 获得被选中的值
         *     var othis = data.othis; // 获得 select 元素被替换后的 jQuery 对象`
         */
        listenSelect: function (layFilterName, callback) {
            layui.form.on('select(' + layFilterName + ')', callback)
        },
        listenRadio: function (layFilterName, callback) {
            form.on('radio(' + layFilterName + ')', callback);
        },
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
         * @param {{title?:string, area?:array,width?:string,height?:string,full?:boolean,end?:Function,resize?:boolean}} options
         * @example
         * admin.iframe.open(url,{title:'新窗口标题', area:['500px','400px']});
         */
        open: function (url, options = {}) {
            if (typeof options.area !== "undefined") {
                options.width = options.area[0];
                options.height = options.area[1]
            } else if (admin.util.isEmpty(options.width) && admin.util.isEmpty(options.height)) {
                const width = window.innerWidth, height = window.innerHeight;
                // const width = document.body.clientWidth, height = document.body.clientHeight;

                if (width >= 1000 && height >= 600) {
                    options.width = (width - 40) + 'px';
                    options.height = (height - 20) + 'px';
                } else {
                    options.width = '90%';
                    options.height = '90%'
                }
                // console.log('iframe:', width, height, '=>', options.width, options.height)
            } else if (options.full === true) {
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
                    if (iFrameIndex) {
                        layer.full(iFrameIndex);
                    }
                })
            }

        },
        /**
         * 从父窗口中移除自身
         * @param {boolean} parentRefresh 是否需要通知父窗口刷新
         * @example
         * admin.form.submitFirst(() => {
         *    admin.iframe.close(true);
         * })
         */
        close: function (parentRefresh = false) {
            const index = parent.layer.getFrameIndex(window.name);
            parent.layer.close(index);
            if (parentRefresh) {
                localStorage.setItem('tao.refresh', "yes");
            }

        },
        /**
         * 设置传递数据
         * @param data
         * @returns {this}
         */
        setData: function (data) {
            admin.cache.save('__last__', data);
            return admin.iframe;
        },
        /**
         * 读取子窗口中设置的数据
         */
        readData: function (success) {
            const d = admin.cache.read('__last__', null);
            if (d != null) {
                admin.cache.remove('__last__');
                success(d);
            }
        },
        /**
         * 是否需要刷新页面，跟 admin.iframe.close 配合使用
         * @param {Function} action 刷新时回调函数
         * @example
         * admin.iframe.open(prefix + '/add?pid=' + id, { // 打开子窗口
         *    title: '添加下级栏目',
         *    end: function () {
         *        admin.iframe.hasRefresh(() => { // 检查是否需要刷新页面
         *            admin.table.reloadData();
         *        })
         *    }
         * })
         * @return {boolean}
         */
        hasRefresh: function (action) {
            if (localStorage.getItem('tao.refresh') === 'yes') {
                localStorage.removeItem('tao.refresh');
                if (typeof action === 'function') {
                    action();
                }
                return true;
            }
            return false;
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
        // 用户上传文件管理，已经移动到 src/App/Modules/tao/Helper/LayuiForm.php 中
    },
    /**
     * 表格数据
     * 对表格数据进行搜索过滤（不发送新请求），查看 admin.util 中的示例
     * @link https://layui.dev/docs/2/table/
     */
    table: {
        /**
         * table 的配置信息
         * @link https://layui.dev/docs/2/table/#options
         * @private
         */
        _config: {
            id: 'table', // table 所在的 div ID
            url: '',
            autoApi: true,
            // https://layui.dev/docs/2/table/#table.render 表格实例
            tableInst: null,
            key: 'id', // 每1行数据的主键
            query: null,// 其它参数
            /**
             * 回调，通常在执行 lay-on 之后调用
             */
            rowAction: function () {
            }
        },
        /**
         * 渲染时的表格 div#
         * @returns {string}
         */
        id: function () {
            return this._config.id
        },
        /**
         * 获取表格数据
         * @return {Array}
         */
        data: function () {
            return layui.table.cache[this._config.id] || [];
        },
        /**
         * 获取选中记录的行
         * @returns {*}
         */
        getCheckedRows: function () {
            return table.checkStatus(this._config.id).data;
        },
        /**
         * 获取选中的行的 ID，使用示例查看 doBatchChange 注释
         * @param success
         */
        getRowIds: function (success) {
            const rows = admin.table.getCheckedRows();
            if (rows.length == 0) {
                admin.layer.error('没有选中任何出车记录');
            } else {
                const ids = rows.map(function (d) {
                    return d.id;
                });
                success(ids);
            }
        },
        /**
         *
         * 向服务器发送批量修改
         * ```js
         * admin.util.layOn({batchMaterial: function () {
         *    admin.table.getRowIds((ids) => {
         *       admin.layer.radiosDialog('修改物料种类', tripMaterial, function (index, value) {
         *           admin.table.doBatchChange(ids, 'material', value, index);
         *       })
         *    })
         * });
         * ```
         * @param {Array<number>} ids 待修改的记录的 ID
         * @param {String} field 待修改的字段名称
         * @param {String|number} value 修改后的值
         * @param index 关闭的 layer.open 的索引
         */
        doBatchChange: function (ids, field, value, index) {
            admin.ajax.postLimit({
                url: this._config.url + '/batchChange',
                data: {ids, field, value}
            }, function (res) {
                if (index) layer.close(index);
                layer.msg('已更新 ' + (ids.length) + ' 条记录', {
                    icon: 1,
                    time: 1500
                }, function () {
                    admin.table.reloadData();
                });
            });
        },
        /**
         * 获取 admin.table 的配置信息（不是 layui.table 的配置）
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
        getTableConfig: function () {
            return layui.table.config[this._config.id];
        },
        /**
         * 初始化表格配置
         * @param {{id?:string, url?:string, rowAction?:function,query?:array}} [config]
         * @return this
         */
        with: function (config = {}) {
            Object.assign(this._config, {url: admin.ajax.apiURL()}, config)
            // console.log(this._config);
            return this;
        },
        /**
         * 获取表格实例
         * <pre>
         * // 实例对象成员
         * inst.config; // 当前表格配置属性
         * inst.reload(options, deep); // 对当前表格的完整重载。参数 deep 表示是否深度重载。
         * inst.reloadData(options, deep); // 对当前表格的数据重载。参数 deep 同上。
         * inst.resize(); // 对当前表格重新适配尺寸
         * inst.setColsWidth() // 对当前表格重新分配列宽
         * </pre>
         * @returns {null}
         */
        getTableInst: function () {
            return this._config.tableInst;
        },
        /**
         * 重新(请求)加载表格数据
         * @link https://layui.dev/docs/2/table/#table.renderData
         */
        reloadData: function () {
            this._config.tableInst.reloadData();
        },
        /**
         * 表格渲染<pre>
         * lineStyle: 'height: 95px;' 多行樣式
         * </pre>
         * https://layui.dev/docs/2/table/#options
         * @param options 表格 render 时配置信息
         * @param {string} [options.url] 发送异步请求的 URL。
         * @param {boolean|Object} [options.page] 用于开启分页
         * @param {number} [options.limit] 每页显示的条数。值需对应 limits 参数的选项。优先级低于 page 属性中的 limit 属性。
         * @param {Array} [options.limits] 每页条数的选择项。示例 [10,…,90]
         * @param {string} [options.lineStyle] 用于定义表格的多行样式，如每行的高度等。该参数一旦设置，单元格将会开启多行模式，且鼠标 hover 时会通过显示滚动条的方式查看到更多内容。 请按实际场景使用。示例：lineStyle: 'height: 95px;'
         * @param {string} [options.className] 用于给表格主容器追加 css 类名，以便更好地扩展表格样式
         * @param {string} [options.css] 用于给当前表格主容器直接设定 css 样式，样式值只会对所在容器有效，不会影响其他表格实例。如：css: '.layui-table-page{text-align: right;}'
         * @param {string|boolean} [options.toolbar] 开启表格头部工具栏。支持多咱写法，如 toolbar: '#template-id' 自定义工具栏模板选择
         * @param {Array|boolean} [options.defaultToolbar] 设置头部工具栏右上角工具图标，值为一个数组，图标将根据数组值的顺序排列。如果为 true，则使用默认
         * @param {Array} [options.cols] 表头属性集，通过二维数组定义多级表头。方法渲染时必填。
         * @param {boolean} [options.page] 是否分页
         * @param {Object} [options.where] 请求的其他参数。原 table 只支持静态数据，如：where: {token: 'sasasas', id: 123}；现在扩展支持 function，要求返回一个 {}
         * @param {Object} [options.headers] 请求的数据头参数。如：headers: {token: 'sasasas'}
         * @param {Function} [options.done] 数据渲染完毕的回调函数。返回的参数如下 function(res, curr, count, origin)
         * res:当前渲染数据（非 api 数据），curr:当前页码，count:数据总量，origin:回调函数所执行的来源
         * @param additions 其它附加功能
         * @param {boolean} [additions.search] 是否开启表格搜索功能，如果是，则页面需要存在 '#' + tableId + '-search' 的元素
         * @param {Function} [additions.success] 成功请求数据响应
         * @param {function} [additions.onSummary] 有统计数据时回调(summary)，数据来自 appendToSuccessPaginationData
         * @param {string} [additions.summaryQueryName] 用于表示当前查询是否开启了统计功能，默认为 indexSummary
         * @param {string} [additions.summaryFieldName] 用表表示统计数据在返回数据中的字段名，默认为 summary
         * @return this
         */
        render: function (options, additions = {}) {
            // 表格 ID
            const tableId = this._config.id;
            // 其它功能
            const adds = Object.assign({
                search: true,
            }, additions)
            // 是否设置为统计数据回调
            const hasOnSummary = typeof adds.onSummary == 'function';
            const summaryFieldName = additions['summary'] || 'summary'; // 统计数据名称
            const summaryQueryName = additions['summaryQueryName'] || 'indexSummary';

            if (options['defaultToolbar'] === true) {
                options['defaultToolbar'] = [ // 默认开启
                    'filter', // 列筛选
                    'exports', // 导出
                    'print', // 打印
                ];
            }

            const config = Object.assign({
                // id: '', 设定实例唯一索引，以便用于其他方法对 table 实例进行相关操作，默认为 id;(this._config.key)
                elem: '#' + tableId, // 绑定原始 table div 元素
                url: admin.util.concatQuery(this._config.url, this._config.query),
                defaultToolbar: [], // 开启表格头部工具栏, 默认不需要
                page: true, // 用于开启分页。
                // height: 'full-20', // 高度最大化，并减去20px的差值，通常用于固定表头；或者使用 .tao-table-fixed
                ajax: function (origOptions, type) {
                    if (typeof options['where'] === 'function') {
                        origOptions.data = Object.assign({}, options['where'](), origOptions.data);
                    }
                    $.ajax({
                        url: origOptions.url,
                        data: origOptions.data,
                        dataType: origOptions.dataType,
                    })
                        .done(function (data) {
                            if (hasOnSummary) {
                                const hasIndexSummary = origOptions.data.hasOwnProperty(summaryQueryName) && [1, 'on', true].includes(origOptions.data[summaryQueryName]);
                                const isFirstPage = origOptions.data.page == 1 || options['page'] == false; // 必须是首页
                                if (hasIndexSummary) { // 使用了统计功能
                                    if (isFirstPage) {
                                        adds.onSummary(data.data[summaryFieldName]);
                                    } // 不是首页时，不更新统计数据
                                } else {
                                    adds.onSummary(null); // 清空统计数据
                                }
                            }
                            // 调用原始的 success 回调
                            origOptions.success(data);
                        })
                        .fail(function (xhr, status, error) {
                            // 调用原始的 error 回调
                            origOptions.error(xhr, status, error);
                        })
                        .always(function () {
                            // 调用原始的 complete 回调
                            if (typeof origOptions.complete === "function") {
                                origOptions.complete();
                            }
                        });
                },
                // 异步属性 借助 parseData 回调函数将数据解析并转换为默认规定的格式
                parseData: function (res) { // res 即为原始返回的数据
                    if (typeof additions['success'] == 'function') {
                        additions['success'](res);
                    }

                    return {
                        "code": res.code, // 解析接口状态
                        "msg": res.message, // 解析提示文本
                        "count": res.data.count, // 解析数据长度
                        "data": res.data.rows, // 解析数据列表
                    };
                },
            }, options)
            if (config.page !== false && admin.util.isEmpty(config['limit'])) {
                config['limit'] = 15;
                config['limits'] = [1, 15, 30, 50, 100];
            }

            // 渲染，并获得实例对象
            const tableInst = layui.table.render(config);

            // 搜索框，需要指定的格式
            const tableSearchElem = $('#' + tableId + '-search');
            if (adds.search) {
                if (tableSearchElem.length === 1) {
                    // 重置按钮
                    const resetElem = tableSearchElem.find('button[type=reset]');
                    if (resetElem.length === 1) {
                        resetElem.bind('click', function () {
                            const reloadData = {
                                where: {reset: 1},
                                page: {curr: 1}
                            }
                            if (options['page'] === false) {
                                delete reloadData.page;
                            }
                            tableInst.reloadData(reloadData)
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
                            const reloadData = {
                                where: form.val('form-search'),
                                page: {curr: 1}
                            };
                            if (options['page'] === false) {
                                delete reloadData.page;
                            }
                            tableInst.reloadData(reloadData)
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
                    if (admin.debug()) {
                        console.log('开启了条件搜索，但没有找到 <fieldset id="' + tableId + '-search">')
                    }
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
            this._config.tableInst = tableInst;
            return this;
        },

        /**
         * 监听表格工具栏的 batchDelete/create 事件；如果需要监听其它独立事件，使用 admin.utils.layOn
         * @param {{url?:string}} [config] 配置信息
         * @return this
         */
        addToolbarActions: function (config = {}) {
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
                                url: url + '/delete', data: {id: ids.join(',')},
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
                        // full: !$(this).attr('data-auto'),
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
         * 添加自定义事件
         * @param {string} name lay-on 绑定的事件名称
         * @param {string} title 打开页面的标题
         * @param {string} url URL
         * @returns {admin.table}
         */
        addLayon: function (name, title, url) {
            const tableId = this._config.id;
            layui.util.on('lay-on', {
                [name]: function () {
                    admin.iframe.open(url, {
                        title: title,
                        end: function () {
                            admin.iframe.hasRefresh(() => {
                                layui.table.reload(tableId);
                                admin.table._config.rowAction(name);
                            })
                        }
                    })
                }
            });
            return this;
        },
        /**
         * 追加 row-action 中 lay-event 自定义事件
         * @param action {Function} 第一个参数事件名称 `obj.event`，第二个参数为行数据 `obj.data`
         */
        events: function (action) {
            const tableId = this._config.id;
            layui.table.on('tool(' + tableId + ')', function (obj) {
                action(obj.event, obj.data);
            });
        },
        /**
         * 监听行操作事件，行操作通常通过 lay-event 进行绑定，默认已经绑定了 edit/delete/remove 事件；
         * 如果需要添加更多事件，可以使用 `admin.table.events(function(eName, data){})`
         * @example
         * html
         * <a class="layui-btn layui-btn-xs" lay-event="edit">编辑</a>
         * js
         * addRowActions({
         *     events: function (d) { // 处理 edit/delete/remove 的操作
         *         const customerId = d.data.customer_id;
         *         const data = form.val('form-search');
         *         const query = '?search=1&month=' + data['month'] + '&customer_id=' + customerId;
         *         switch (d.event) {
         *             case 'payment':
         *                 const url1 = 'echo \Phax\Foundation\AppService::urlModule("yihe/payment")' + query;
         *                 admin.iframe.open(url1, {title: '客户付款记录'});
         *                 break;
         *             case 'trip':
         *                 const url2 = 'echo \Phax\Foundation\AppService::urlModule("yihe/trip")' + query;
         *                 admin.iframe.open(url2, {title: '客户出车记录'});
         *         }
         *     }
         * })
         * @param {{url?:string, events?:Function, key?:string}} [config] 回调函数，obj.event 是事件名称, obj.data 是当前行数据
         * @return this
         */
        addRowActions: function (config = {}) {
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
                        if (typeof config['beforeDelete'] === 'function') {
                            // TODO 删除前
                        }
                        admin.layer.confirm('确定要删除当前记录吗！', function () {
                            admin.ajax.postLimit({
                                    url: url + '/delete', data: {
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
        money: function (data) {
            const v = data[this.field];
            return admin.util.formatMoney(v, true);
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
                return '<img style="max-width: ' + option.imageWidth + 'px; max-height: ' + option.imageHeight + 'px;" data-image="' + option.title + '">';
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
         * 渲染 switch 模板
         * @param data {Object} 包含当前行数据及特定的额外字段
         * @return string
         * @example
         * ```
         * {field: 'status', title: '状态', width: 95, templet: admin.table.switch},
         * // tips: '正常|禁用',
         * ```
         */
        switch: function (data) {
            // this => {"field": "status","title": "状态","width": 85,"key": "1-0-5",
            //     "colspan": 0,"rowspan": 0,"type": "normal","colGroup": false,"hide": false
            // } 等价于 data[LAY_COL]
            // data => {LAY_COL, LAY_INDEX:0, LAY_NUM:1, ...模型数据}
            const option = {
                field: this.field,
                value: data[this.field],
                // selectList: this.selectList || {0: '禁用', 1: '启用'}, // 没啥用
                tips: this.tips || '显示|隐藏',
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
            const dataType = typeof data;
            const v = dataType === 'string' || dataType === 'number' ? data : data[this.field];
            return admin.util.humanTime(v);
        },
        humanDate: function (data, useV = false) {
            const v = useV === true ? data : data[this.field];
            return admin.util.humanDate(v);
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
        listenCellEdit: function (tableId, callback) {
            table.on('edit(' + tableId + ')', callback)
        },
        /**
         * 添加单元格编辑事件, 如 `{field:'city', title: '城市', width:80, edit: true},`
         * @link https://layui.dev/docs/2/table/#demo-editable
         * @param {{url?:string,ok?:Function}} [config] 配置信息
         * @return this
         */
        addCellEditAction: function (config) {
            const tableId = this._config.id;
            const url = config && config.url ? config.url : this._config.url;

            admin.table.listenCellEdit(tableId, function (obj) {
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
         * 移除符合条件的数据，并重载数据
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
     * layui 缓存 <br>
     * 本地存储是对 localStorage 和 sessionStorage 的友好封装，可更方便地管理本地数据。方法如下：<br>
     * layui.data(table, settings); 即 localStorage，数据在浏览器中的持久化存储，除非物理删除。<br>
     * layui.sessionData(table, settings); 即 sessionStorage ，数据在浏览器中的会话性存储，页面关闭后即失效。
     * @link https://layui.dev/docs/2/base.html#data
     */
    cache: {
        dbIndex: '__phax__',
        /**
         * 读取缓存
         * @param {string} key
         * @param {any} defV 默认值
         */
        read: function (key, defV) {
            const local = layui.data(this.dbIndex);
            const v = local[key];
            if (admin.util.isEmpty(v)) {
                return defV;
            }
            return JSON.parse(v);
        },
        save: function (key, data) {
            layui.data(this.dbIndex, {
                key: key, value: JSON.stringify(data),
            })
        },
        remove: function (key) {
            layui.data(this.dbIndex, {
                key: key, removeActive: true,
            })
        },
        clear: function () {
            localStorage.clear();
        },

    },
    /**
     * 移动端滚动加载（Infinite Scroll）
     * 统一管理分页/加载状态/滚动检测
     * @example
     * admin.mobilePage.init({
     *     url: '/yihe/customer',
     *     getSearchData: function() { return {keyword: 'xxx'}; },
     *     renderCards: function(rows) { return '<div>...</div>'; },
     *     onData: function(rows, append) {// 更新底部汇总等
     * });
     */
    mobilePage: {
        opts: null,
        currentPage: 1,
        totalPages: 1,
        isLoading: false,
        hasMore: true,

        /**
         * 初始化移动端列表页面
         * @param {object} opts
         * @param {string} opts.url        - API URL
         * @param {boolean} [opts.page]  - 是否开启分页，默认 true
         * @param {number} [opts.pageSize]  - 每页条数，默认 20
         * @param {function} [opts.getSearchData] - 返回搜索参数字典
         * @param {function} [opts.renderCards]   - 接收 rows 数组，返回卡片 HTML
         * @param {function} [opts.onData]        - 每次加载完成回调(rows, append)
         * @param {function} [opts.onSummary]     - 有统计数据时回调(summary)，数据来自 appendToSuccessPaginationData
         */
        init: function (opts) {
            const self = this;
            if (opts.page === false) {
                opts.pageSize = 999999;
            }
            self.opts = opts;
            self.currentPage = 1;
            self.totalPages = 1;
            self.isLoading = false;
            self.hasMore = true;

            // 开始加载数据
            self.load(false);

            // 搜索表单提交与重置
            layui.use('form', function () {
                const form = layui.form;
                form.on('submit', function () {
                    self.load(false);
                    return false;
                });
                $('button[type="reset"]').on('click', function () {
                    setTimeout(function () {
                        self.load(false);
                    }, 50);
                });
            });

            // 滚动加载：距底部 50px 时自动加载下一页
            $(window).on('scroll', function () {
                if (!self.hasMore || self.isLoading) return;
                const scrollBottom = $(document).height() - $(window).height() - $(window).scrollTop();
                if (scrollBottom < 50) {
                    self.load(true);
                }
            });
        },

        /**
         * 加载数据
         * @param {boolean} append - true=追加到尾部，false=重新加载
         */
        load: function (append) {
            const self = this;
            const opts = self.opts;
            if (self.isLoading) return;
            self.isLoading = true;
            if (!append) {
                self.currentPage = 1;
                self.totalPages = 1;
                self.hasMore = true;
                $('#cardList').html('');
            }
            $('#loading').show();

            const data = admin.util.filter(opts.getSearchData ? opts.getSearchData() : {});
            const pageSize = opts.pageSize || 20;
            data.page = self.currentPage;
            data.limit = pageSize;

            $.get(opts.url, data, function (res) {
                self.isLoading = false;
                $('#loading').hide();
                if (res.code !== 0) {
                    if (!append) $('#cardList').html('<div class="empty-state">请求失败：' + (res.msg || '未知错误') + '</div>');
                    return;
                }
                const rows = res.data.rows || [];
                const count = res.data.count || 0;
                self.totalPages = Math.max(1, Math.ceil(count / pageSize));
                self.hasMore = self.currentPage < self.totalPages;
                if (rows.length === 0 && !append) {
                    $('#cardList').html('<div class="empty-state">📭 暂无数据</div>');
                    return;
                }
                var html = '';
                if (opts.renderCards) {
                    html = opts.renderCards(rows);
                }
                if (!self.hasMore && rows.length > 0) {
                    html += '<div style="text-align:center;padding:16px;color:#999;font-size:13px;">— 已加载全部 —</div>';
                }
                if (append) {
                    $('#cardList').append(html);
                } else {
                    $('#cardList').html(html);
                }
                if (opts.onData) {
                    opts.onData(rows, append);
                }
                if (typeof opts.onSummary == 'function') {
                    if ([1, 'on'].includes(data.indexSummary)) { // 使用了统计功能
                        if (data.page == 1) {
                            opts.onSummary(res.data.summary);
                        } // 不是首页时，不更新统计数据
                    } else {
                        opts.onSummary(null); // 清空统计数据
                    }
                }
                self.currentPage++;
            }, 'json').fail(function () {
                self.isLoading = false;
                $('#loading').hide();
                if (!append) $('#cardList').html('<div class="empty-state">❌ 网络请求失败</div>');
            });
        }
    }
};