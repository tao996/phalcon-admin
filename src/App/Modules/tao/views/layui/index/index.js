//头部事件
layui.util.event('lay-header-event', {
    menuLeft: function (othis) { // 左侧菜单事件
        layui.layer.msg('展开左侧菜单的操作', {icon: 0});
    },
    menuRight: function () {  // 右侧菜单事件
        layui.layer.open({
            type: 1,
            title: '更多',
            content: '<div style="padding: 15px;">处理右侧面板的操作</div>',
            area: ['260px', '100%'],
            offset: 'rt', // 右上角
            anim: 'slideLeft', // 从右侧抽屉滑出
            shadeClose: true,
            scrollbar: false
        });
    }
});


const flexibleElement = $('#LAY_app_flexible');
const appElement = $('#LAY_app');

// 自动初始化

// 监听
admin.util.layOn({
    // 侧边收缩
    flexible: function () {
        const isRight = flexibleElement.hasClass('layui-icon-shrink-right');
        flexibleElement
            .removeClass(isRight ? 'layui-icon-shrink-right' : 'layui-icon-spread-left')
            .addClass(isRight ? 'layui-icon-spread-left' : 'layui-icon-shrink-right')
        if (isRight) {
            appElement.addClass('layadmin-side-shrink')
        } else {
            appElement.removeClass('layadmin-side-shrink')
        }
    },
    // 切换模块菜单
    switchSidebar: function () {
        const id = $(this).attr('data-id');
        tabs.activeSidebar(id);
        tabs.tmpMenuId = id;
    },
    refresh: function () {
        tabs.refreshCurrent();
        admin.layer.success('刷新页面成功');
    },
})

// 标签事件
const tabsCache = {

    _key: 'tabs',
    defaultData: function () {
        return {menuId: 0, active: {id: 0, href: '', title: ''}};
    },
    save: function (active) {
        const old = this.read();
        const data = {
            menuId: tabs.menuId > 0 ? tabs.menuId : old.menuId,
            active: active
        }
        admin.cache.save(this._key, data);
    },
    remove: function (href) {
        const def = this.defaultData();
        const tab = admin.cache.read(this._key, def);
        if (tab.active.href === href) {
            tab.active = def.active;
            admin.cache.save(this._key, tab);
        }
    },
    read: function () {
        return admin.cache.read(this._key, this.defaultData());
    },
    clear: function () {
        admin.cache.remove(this._key);
    }
};
// iframe tabs
const tabs = {
    menuId: 0, // 当前激活菜单所属的 sidebar id
    activeLayHref: '',// 当前激活的 ID
    container: $('#LAY_app_tabsheader'), // tab 列表
    iframeElementContainer: $('#LAY_app_body'), // iframe 列表
    tmpMenuId: 0, // sidebar id（点击而已）
    /**
     * 激活菜单（顶部大菜单，一级菜单，二级菜单）
     * @param id {string} 顶部大菜单 ID

     * @param reset
     */
    activeSidebar: function (id, reset = false) {
        this.menuId = id;
        const cMenuId = this.getCurrentMenuParentId(id);
        const activeNav = $('#layui-nav-' + cMenuId);
        if (activeNav.length > 0) {
            $('.layui-nav-tree').addClass('layui-hide');
            activeNav.removeClass('layui-hide');
            if (reset) {
                $('.switchSidebar').removeClass('layui-this');
                $('#switchSidebar-' + cMenuId).addClass('layui-this');
            }
        }
    },
    elements: function () {
        return this.container.find('li');
    },
    iframeElements: function () {
        return this.iframeElementContainer.find('div.layadmin-tabsbody-item');
    },
    ids: function () {
        const layIds = [];
        for (const li of this.elements()) {
            layIds.push(li.getAttribute('lay-id'));
        }
        return layIds;
    },
    removeActiveClass: function () {
        this.elements().removeClass('layui-this')
        this.iframeElements().removeClass('layui-show');
    },
    // 激活指定的索引
    addActiveClass: function (index) {
        this.elements()[index].classList.add('layui-this');
        this.iframeElements()[index].classList.add('layui-show');
    },
    remove: function (index) {
        this.elements()[index].remove();
        this.iframeElements()[index].remove();
    },
    // 关闭一个标签
    close: function (href) {
        window.event.cancelBubble = true;
        const ids = this.ids();
        const index = ids.indexOf(href);
        if (href === this.activeLayHref) {
            this.activeLeft();
        }
        this.remove(index);
        tabsCache.remove(href);
    },
    // 添加一个标签 {id,href,title} 到 iframe 中; 如果已经存在，则激活它
    // tabs.append({id:8,title:'单页管理',href:'/m/tao.cms/admin.page'})
    append: function (info, cache = true) {
        if (info.href === this.activeLayHref) {
            return;
        }
        this.activeLayHref = info.href;
        if (cache) {
            tabsCache.save(info);
        }
        const hrefs = tabs.ids();
        const index = hrefs.indexOf(info.href);
        // console.log('info', info, hrefs, index)
        tabs.removeActiveClass();
        if (index === -1) {
            tabs.container.append(`<li lay-id="${info.href}" class="layui-this" onclick="tabs.append({id:'${info.id}',title:'${info.title}',href:'${info.href}'})">
<span>${info.title}</span>
<i class="layui-icon layui-icon-close layui-unselect layui-tab-close"
onclick="tabs.close('${info.href}')"></i>
</li>`);
            admin.layer.load();
            // 添加 iframe
            tabs.iframeElementContainer.append(`<div class="layadmin-tabsbody-item layui-show">
    <iframe src="${info.href}"
            frameborder="0" class="layadmin-iframe"></iframe>
</div>`);
        } else {
            this.addActiveClass(index);
        }
    },
    // 激活左边标签
    activeLeft: function () {
        const ids = this.ids();
        const index = ids.indexOf(this.activeLayHref);
        if (index >= 1) {
            this.removeActiveClass();
            this.addActiveClass(index - 1);
            this.activeLayHref = ids[index - 1];
        }
    },
    // 激活右边标签
    activeRight: function () {
        const ids = this.ids();
        const index = ids.indexOf(this.activeLayHref);
        if (-1 < index && index < ids.length - 1) {
            this.removeActiveClass();
            this.addActiveClass(index + 1);
            this.activeLayHref = ids[index + 1]
        }
    },
    // 关闭当前标签页（第1个无法关闭）
    removeCurrent: function () {
        const ids = this.ids();
        const index = ids.indexOf(this.activeLayHref);
        if (index > 0) {
            this.activeLeft();
            this.remove(index);
        }
    },
    refreshCurrent: function () {
        const ids = this.ids();
        const index = ids.indexOf(this.activeLayHref);
        const iframe = this.iframeElements()[index].childNodes[1];
        iframe.contentWindow.location.reload();
    },
    // 关闭其它标签
    removeOthers: function () {
        const ids = this.ids();
        const index = ids.indexOf(this.activeLayHref);

        for (let i = ids.length - 1; i > index; i--) {
            this.remove(i);
        }
        for (let i = index - 1; i > 0; i--) {
            this.remove(i);
        }
    },
    // 关闭全部标签（除了第1个）
    removeAll: function () {
        const ids = this.ids();
        for (let i = ids.length - 1; i > 0; i--) {
            this.remove(i);
        }
        this.activeLayHref = ids[0];
    },
    /**
     * 恢复激活菜单
     * @param sMenu {boolean} 是否激活左侧子菜单
     * @param bMenu {boolean} 是否激活顶部菜单
     */
    recoverFromCache: function (sMenu = true, bMenu = true) {
        const data = tabsCache.read();
        // console.log(data.active, this.getCurrentMenuParentId(data.active.id), this.getCurrentMenuId(data.active.id))
        if (sMenu) {
            tabs.append(data.active);
            const parentId = this.getCurrentMenuParentId(data.active.id);

            if (this.isSubMenuId(data.active.id)) {
                $('#layui-nav-item-' + parentId).addClass('layui-nav-itemed')
                const id = this.getCurrentMenuId(data.active.id);
                $('#layui-nav-item-' + id).addClass('layui-this');
            } else {
                $('#layui-nav-item-' + parentId).addClass('layui-this');
            }
        }
        if (bMenu) {
            this.activeSidebar(data.menuId, true);
        }
    },
    /**
     * 当前菜单 ID
     * @param id {string}
     * @return {number}
     */
    getCurrentMenuId: function (id) {
        return parseInt(id.includes('-') ? id.split('-')[1] : id);
    },
    /**
     * 待显示的一级菜单 ID
     * @param id {string}
     * @return {number}
     */
    getCurrentMenuParentId: function (id) {
        return parseInt((''+id).indexOf('-') > -1 ? id.split('-')[0] : id);
    },
    isSubMenuId: function (id) {
        return (''+id).includes('-');
    }
};

layui.util.on('layadmin-event', {
    leftPage: function () {
        tabs.activeLeft();
    },
    rightPage: function () {
        tabs.activeRight();
    },
    // 关闭当前标签页
    closeThisTabs: function () {
        tabs.removeCurrent();
    },
    // 关闭其它标签页
    closeOtherTabs: function () {
        tabs.removeOthers();
    },
    // 关闭全部标签页
    closeAllTabs: function () {
        tabs.removeAll();
        tabs.addActiveClass(0)
    },
});

function bindPageEvents() {
    $('a[lay-href]').bind('click', function () {
        const href = this.getAttribute('lay-href');
        const title = this.getAttribute('data-tips'); // 标题
        const id = this.getAttribute('data-id');
        tabs.append({id, href, title});
    });
}

bindPageEvents();
tabs.recoverFromCache();

/**
 * 添加一个 TAB, 在子页中使用 parent.appendTab() 调用
 * @param {string} title 显示的标题
 * @param {string} href 链接地址
 * @param {number} id 菜单ID，默认为0
 */
function appendTab(title, href, id = 0) {
    tabs.append({id, title, href}, false)
}

// 子页面通知父页面更新模块及其菜单 parent.notifyUpdateMenu();
function notifyUpdateMenu() {
    // 拼接菜单
    admin.ajax.get({}, function (res) {
        const mTree = res.data.menuTree;
        const data = tabsCache.read();
        const menuId = tabs.tmpMenuId;

        document.getElementById('menuTree').innerHTML = mTree.map(function (m1, index1) {
            const isActive = menuId == m1['id'];
            let ul = `<ul id="layui-nav-${m1.id}" class="layui-nav layui-nav-tree ${isActive || (menuId === 0 && index1 === 0) ? 'layui-this' : 'layui-hide'}" lay-filter="left-nav">`;

            if (m1['child'] && m1['child'].length > 0) {
                ul += m1['child'].map(function (m2, index2) {
                    const hasChildren = m2['child'] && m2['child'].length > 0;
                    let li = `<li class="layui-nav-item" id="layui-nav-item-${m2['id']}">
<a ${hasChildren ? 'href="javascript:;"' : `lay-href="${m2['href']}"`} data-tips="${m2['title']}" data-id="${m2['id']}">
    <i class="${m2['icon']}"></i><cite>${m2['title']}</cite>
</a>`;

                    if (hasChildren) {
                        li += `<dl class="layui-nav-child">`;
                        li += m2['child'].map(function (m3) {
                            return `<dd data-name="">
<a lay-href="${m3['href']}" data-tips="${m3['title']}" data-id="${m3['id']}">${m3['title']}</a>
</dd>`;
                        }).join('')
                        li += `</dl>`;
                    }

                    li += '</li>';
                    return li;
                }).join('');
            }

            ul += '</ul>';
            return ul;
        }).join('');
        bindPageEvents(); // 重新绑定
        // https://layui.dev/docs/2/nav/#render
        layui.element.render('nav', 'left-nav');
        tabs.recoverFromCache(true, false)
    })
}