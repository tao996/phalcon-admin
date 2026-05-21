/**
 * 返回实现
 * @param {{id:string,title:string, prefix:string, methods?:Object, key?:string, doData?:function,query?:string}} options 配置信息
 * @param {Array} items 初始化的值
 */
function vueArray(options, items = []) {
    if (typeof options.methods === 'undefined') {
        options.methods = {}
    }
    if (typeof options.key === 'undefined') {
        options.key = options.id;
    }
    if (admin.util.isEmpty(options.query)) {
        options.query = '';
    }

    const hasDoData = typeof options.doData === 'function';

    const app = Vue.createApp({
        data() {
            return {
                editIndex: -1,
                items: items,
                removes: [], // 移除的项
            }
        },
        computed: {
            itemsLength() { // 当前项的长度
                return this.items.length;
            },
            removesLength() { // 删除记录的长度
                return this.removes.length;
            }
        },
        methods: {
            editWith(ii) { // 编辑记录
                const item = this.items[ii];
                // 缓存当前记录，以便编辑页面能够使用它
                admin.storage.save(options.key, item);
                this.editIndex = ii;
                if (admin.debug()) {
                    console.log(options.id + ' edit with ', ii, item)
                }
                admin.iframe.open(options.prefix + '/edit?storage=true&id=' + item.id, {
                    title: '修改',// + options.title,
                    end: () => {
                        admin.storage.getParse(options.key, row => {
                            if (admin.debug()) {
                                console.log(options.id + ' edit storage', row)
                            }
                            this.items[ii] = hasDoData ? options.doData(row, false) : Object.assign(this.items[ii], row);
                        });
                    }
                })
            },
            insert() {
                admin.iframe.open(options.prefix + '/add?storage=true', {
                    title: '添加' + options.title,
                    end: () => {
                        admin.storage.getParse(options.key, row => {
                            if (admin.config) {
                                console.log(options.id + ' insert storage', row)
                            }
                            this.items.push(hasDoData ? options.doData(row, false) : row);
                        });
                    }
                })
            },
            // 从已有中导入
            select: function () {
                admin.iframe.open(options.prefix + '/select' + options.query, {
                    title: options.title + '选择',
                    end: () => {
                        admin.storage.getArray(options.key, rows => {
                            if (admin.debug()) {
                                console.log(options.id + ' select', rows)
                            }
                            const items = hasDoData ? options.doData(rows, true) : rows;
                            this.items.push(...items)
                        });
                    }
                });
            },
            removeWith(ii) { // 删除指定记录
                layer.confirm('确定要移除当前添加吗？此操作可撤销。', index => {
                    const item = this.items[ii];
                    item['index'] = ii;
                    this.removes.push(item);
                    this.items.splice(ii, 1);
                    layer.close(index)
                })
            },
            moveToPre(ii) { // 向前移动
                if (ii === 0) {
                    layer.msg('已经是第一个了')
                } else {
                    [this.items[ii], this.items[ii - 1]] = [this.items[ii - 1], this.items[ii]]
                }
            },
            moveToNext(ii) { // 向后移动
                if (ii === this.itemsLength - 1) {
                    layer.msg('已经是最后一个了')
                } else {
                    [this.items[ii], this.items[ii + 1]] = [this.items[ii + 1], this.items[ii]]
                }
            },
            recoverDelete() { // 取消删除
                const last = this.removes.pop();
                const index = last.index;
                delete last.index;
                this.items.splice(index, 0, last)
            },
            ...options.methods // 追加其它方法
        }
    });
    app.mount('#' + options.id)
    return app._instance;
    // 可以通过 ins.data.xx 访问 data 属性
}