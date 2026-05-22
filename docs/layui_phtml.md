# Layui PHTML 视图编写指南

> 适用于 `App\Modules\tao` 模块的后台管理视图。前端基于 Layui 2.9 构建，集成了 FontAwesome 图标和自定义的 `admin.*` JS 工具库。

## 1. 视图文件的位置规则

```
src/App/Modules/tao/views/{theme}/{controller-dir}/{action}.phtml
```

- `{theme}` — 默认为 `layui`
- `{controller-dir}` — 控制器在 `Controllers/` 下的子目录名，如 `admin/user/`
- `{action}` — 对应控制器的方法名（去掉 `Action` 后缀）

**示例**：

| 控制器类文件                                        | 对应视图路径                                      |
|-----------------------------------------------|---------------------------------------------|
| `Controllers/admin/UserController.php`        | `views/layui/admin/user/index.phtml`        |
| `Controllers/admin/UserController.php`        | `views/layui/admin/user/edit.phtml`         |
| `Controllers/admin/MenuController.php`        | `views/layui/admin/menu/index.phtml`        |
| `A0/app/Controllers/admin/InfoController.php` | `A0/app/views/layui/admin/info/index.phtml` |

**约定**：`add.phtml` 通常只需要 `require_once __DIR__ . '/edit.phtml';` 一行——添加和编辑共用同一个表单视图。

---

## 2. 视图中的可用变量与助手方法

每个视图顶部用 `@var` 声明可用的变量：

```phtml
<?php
/** @var \App\Modules\tao\Helper\MyMvcHelper $vv */
?>
```

### 2.1 核心助手 `$vv`

| 方法                                             | 用途                           | 示例                                           |
|------------------------------------------------|------------------------------|----------------------------------------------|
| `$vv->pick('field')`                           | 获取控制器返回数组中的值                 | `$vv->pick('title')`                         |
| `$vv->pick('field', 'default')`                | 带默认值                         | `$vv->pick('status', 1)`                     |
| `$vv->urlModule("tao/module.ctrl")`            | 生成模块 URL 前缀                  | `/m/tao/admin.user`                          |
| `$vv->html()`                                  | HtmlHelper（高级 DOM 操作）        | —                                            |
| `$vv->html()->pickCompare('field', 'checked')` | 比较输出 `checked`               | 见复选框用法                                       |
| `$vv->layui()`                                 | Layui 实例（header/footer 资源引用） | —                                            |
| `$vv->layuiHtml()`                             | 预制 UI 组件                     | 上传、图标选择                                      |
| `$vv->configService()`                         | 系统配置服务                       | `configService()->getWith('site.site_name')` |
| `$vv->request()`                               | 当前请求对象                       | —                                            |

### 2.2 标准视图尾部

每个视图文件**必须**在结束前调用（通常是 `</script>` 之后）：

```phtml
<?php $vv->layui()->addWindowConfig([])->footer(); ?>
```

这行代码输出 Layui 的 JS/CSS 资源引用和弹窗配置。

---

## 3. 列表页

列表页（`index.phtml`）由三部分组成：**搜索区** + **工具栏模板** + **数据表格**。

### 3.1 基础结构

```phtml
<?php /** @var \App\Modules\tao\Helper\MyMvcHelper $vv */ ?>

<!-- ===== 一、搜索区 ===== -->
<fieldset class="table-search-fieldset layui-hide" id="table-search">
    <legend>条件搜索</legend>
    <form class="layui-form layui-form-pane form-search" lay-filter="form-search">
        <div class="layui-form-item layui-inline">
            <label class="layui-form-label">状态</label>
            <div class="layui-input-inline">
                <select name="status">
                    <option value="">全部</option>
                    <option value="1">启用</option>
                    <option value="2">禁用</option>
                </select>
            </div>
        </div>
        <div class="layui-form-item layui-inline">
            <label class="layui-form-label">关键字</label>
            <div class="layui-input-inline">
                <input name="keyword" placeholder="搜索关键字" class="layui-input">
            </div>
        </div>
        <div class="layui-form-item layui-inline">
            <a class="layui-btn layui-btn-normal layui-btn-sm" lay-submit>搜索</a>
            <button type="reset" class="layui-btn layui-btn-primary layui-btn-sm">重置</button>
        </div>
    </form>
</fieldset>

<!-- ===== 二、工具栏模板（laytpl） ===== -->
<script type="text/html" id="toolbar">
    <div class="layui-table-tool-temp">
        <button class="layui-btn layui-btn-sm" lay-on="refresh">
            <i class="fa fa-refresh"></i>
        </button>
        <button class="layui-btn layui-btn-normal layui-btn-sm" lay-on="create">
            <i class="fa fa-plus"></i>添加
        </button>
        <button class="layui-btn layui-btn-sm layui-btn-danger" lay-on="batchDelete">
            <i class="fa fa-trash-o"></i>删除
        </button>
    </div>
</script>

<!-- 行操作按钮模板 -->
<script type="text/html" id="row-action">
    <div class="layui-btn-container">
        <a class="layui-btn layui-btn-xs" lay-event="edit">编辑</a>
        <a class="layui-btn layui-btn-xs layui-btn-danger" lay-event="remove">删除</a>
    </div>
</script>

<!-- ===== 三、表格容器 ===== -->
<table id="table" class="layui-hide"></table>

<?php $vv->layui()->addWindowConfig([])->footer(); ?>

<!-- ===== 四、JS 初始化 ===== -->
<script>
    // 生成 AJAX URL 前缀（固定模式）
    const prefix = '<?php echo $vv->urlModule("tao/admin.role", false) ?>';

    admin.table.with({url: prefix})
        .render({
            toolbar: '#toolbar',
            cols: [[
                {type: 'checkbox'},
                {field: 'id', title: 'ID', width: 50},
                {field: 'title', title: '名称', width: 150},
                {field: 'status', title: '状态', width: 85, templet: admin.table.switch},
                {field: 'created_at', width: 150, title: '创建时间', templet: admin.table.humanTime},
                {title: '操作', width: 180, toolbar: '#row-action'}
            ]],
        })
        .addPostSwitch()       // 开关状态变更自动提交
        .addPostEditText()     // 可编辑单元格自动提交
        .addLayOnActions()     // toolbar 中的按钮事件
        .addLayEventActions(); // row-action 中的按钮事件
</script>
```

### 3.2 几种常用的列渲染模板

```
// 开关（对应数据库 int 类型 status 字段）
{field: 'status', title:'状态', width:85, templet:admin.table.switch}

// 时间戳格式化
{field: 'created_at', width:150, title:'创建时间', templet:admin.table.humanTime}

// 图片缩略图
{field: 'url', width:70, title:'图片', templet:admin.table.image}

// 图标
{field: 'icon', width:60, title:'图标', templet:admin.table.icon}

// 自定义 HTML（function 方式）
{field: 'upload_type', width:100, title:'存储位置',templet:function (d) {
        const map = {local: '本地', alioss: '阿里云'};
        return map[d.upload_type] || '---';
    }
}

// 可直接编辑的文本框
{field: 'sort', title:'排序', edit:text'},
{field: 'remark', title:'备注', edit:'text'}
,
```

### 3.3 搜索字段与后端的对应

```
// 下拉搜索 → controller 中 indexActionQueryBuilder 通过 $queryBuilder->int('status', ...) 接收
{field: 'status', title: '状态', search: true}

// 文本框搜索
{field: 'phone', title: '手机号', search: true}

// 时间范围搜索
{field: 'created_at', width: 150, title: '创建时间', search: 'range'}
```

对应的后端控制器方法：

```php
protected function indexActionQueryBuilder(\Phax\Db\QueryBuilder $qb): void
{
    $qb->int('status', $this->request->getQuery('status', 'int'));
    $qb->like('phone', $this->request->getQuery('phone', 'string'));
    $qb->like('title', $this->request->getQuery('title', 'string'));
}
```

---

## 4. 编辑添加页面

### 4.1 表单基础框架

```phtml
<?php /** @var \App\Modules\tao\Helper\MyMvcHelper $vv */ ?>
<form class="layui-form">

    <!-- 表单项放在这里 -->

    <div class="hr-line"></div>
    <div class="layui-form-item text-center">
        <button type="submit" class="layui-btn layui-btn-normal layui-btn-sm" lay-submit>确认</button>
        <button type="reset" class="layui-btn layui-btn-primary layui-btn-sm">重置</button>
    </div>
</form>
<?php $vv->layui()->addWindowConfig([])->footer(); ?>
<script>
    admin.form.submitFirst(function () {
        admin.iframe.closeFromParent(true);
    });
</script>
```

### 4.2 普通文本输入框

```phtml
<div class="layui-form-item">
    <label class="layui-form-label required">角色名称</label>
    <div class="layui-input-inline">
        <input type="text" name="title" class="layui-input"
               lay-verify="required"
               lay-reqtext="请输入角色名称"
               placeholder="请输入角色名称"
               value="<?php echo $vv->pick('title') ?>">
    </div>
    <div class="layui-form-mid layui-text-em">填写角色名称</div>
</div>
```

**关键属性说明**：

| 属性                                  | 作用                      |
|-------------------------------------|-------------------------|
| `lay-verify="required"`             | 必填验证                    |
| `lay-reqtext="..."`                 | 必填提示文字                  |
| `lay-affix="clear"`                 | 显示清除按钮                  |
| `lay-verify="email\|phone\|number"` | 格式验证                    |
| `layui-form-mid` + `layui-text-em`  | 辅助说明文字                  |
| `layui-form-label required`         | 标签上的 `required` 类显示红色星号 |

### 4.3 多行文本框

```phtml
<div class="layui-form-item layui-form-text">
    <label class="layui-form-label">备注信息</label>
    <div class="layui-input-block">
        <textarea name="remark" class="layui-textarea"
                  placeholder="请输入备注信息"><?php echo $vv->pick('remark') ?></textarea>
    </div>
</div>
```

### 4.4 数字输入框

```phtml
<div class="layui-form-item">
    <label class="layui-form-label">排序</label>
    <div class="layui-input-inline">
        <input type="number" name="sort" class="layui-input"
               placeholder="数字越小越靠前"
               value="<?php echo $vv->pick('sort', 0) ?>">
    </div>
</div>
```

### 4.5 下拉选择框

**静态选项**：

```phtml
<div class="layui-form-item">
    <label class="layui-form-label">状态</label>
    <div class="layui-input-inline">
        <select name="status">
            <option value="1" <?php echo $vv->pick('status') == 1 ? 'selected' : '' ?>>启用</option>
            <option value="2" <?php echo $vv->pick('status') == 2 ? 'selected' : '' ?>>禁用</option>
        </select>
    </div>
</div>
```

**动态选项（来自 PHP 数据）**：

```phtml
<div class="layui-form-item">
    <label class="layui-form-label">上级菜单</label>
    <div class="layui-input-inline">
        <select name="pid">
            <?php foreach ($menuList as $vo) { ?>
                <option value="<?php echo $vo['id'] ?>"
                    <?php echo $pid == $vo['id'] ? 'selected' : '' ?>>
                    <?php echo $vo['title'] ?>
                </option>
            <?php } ?>
        </select>
    </div>
</div>
```

### 4.6 单选框

```phtml
<div class="layui-form-item">
    <label class="layui-form-label">存储方式</label>
    <div class="layui-input-block">
        <input type="radio" name="upload_type" value="local" title="本地"
            <?php echo $vv->html()->pickCompare('upload_type', 'checked', 'local') ?>>
        <input type="radio" name="upload_type" value="alioss" title="阿里云"
            <?php echo $vv->html()->pickCompare('upload_type', 'checked', 'alioss') ?>>
        <input type="radio" name="upload_type" value="qnoss" title="七牛云"
            <?php echo $vv->html()->pickCompare('upload_type', 'checked', 'qnoss') ?>>
    </div>
</div>
```

**简化写法**（循环）:

```phtml
<div class="layui-form-item">
    <label class="layui-form-label">是否启用</label>
    <div class="layui-input-block">
        <?php foreach ([0 => '禁用', 1 => '启用'] as $v => $t) { ?>
            <input type="radio" name="status" value="<?php echo $v ?>"
                   title="<?php echo $t ?>"
                <?php echo $vv->html()->pickCompare('status', 'checked', $v) ?>>
        <?php } ?>
    </div>
</div>
```

### 4.7 复选框（开关）

**单独开关**：

```phtml
<div class="layui-form-item">
    <label class="layui-form-label">是否验证</label>
    <div class="layui-input-inline">
        <input type="checkbox" name="email_valid"
               lay-skin="switch" title="是|否"
            <?php echo $vv->html()->pickCompare('email_valid', 'checked') ?>>
    </div>
</div>
```

`pickCompare` 不带第 3 个参数时，只要值不为空就输出 `checked`。

**分组多选**：

```phtml
<div class="layui-form-item">
    <label class="layui-form-label">角色</label>
    <div class="layui-input-block">
        <?php
        $selected = $vv->pick('role_ids', []);
        foreach ($roleList as $id => $item) { ?>
            <input type="checkbox" name="role_ids[<?php echo $id ?>]"
                   lay-skin="primary" title="<?php echo $item['title'] ?>"
                <?php echo in_array($id, $selected) ? 'checked' : '' ?>>
        <?php } ?>
    </div>
</div>
```

### 4.8 日期时间选择器

```phtml
<div class="layui-form-item">
    <label class="layui-form-label">维护时间</label>
    <div class="layui-input-inline">
        <input type="text" name="maintain_time" class="layui-input" id="maintain_time"
               value="<?php echo $vv->pick('maintain_time') ?>"
               placeholder="请选择时间">
    </div>
</div>
<script>
    admin.date.renderDatetime('maintain_time');  // 日期+时间
    // 或
    admin.date.renderDate('created_at');          // 仅日期
</script>
```

---

## 5. 文件上传组件

### 5.1 使用 `$vv->layuiHtml()->upload()` 快速生成

```phtml
<?php $vv->layuiHtml()->upload('用户头像', 'head_img', [
    'value' => $vv->pick('head_img'),    // 默认值
    'type' => 'hidden',                  // 默认 hidden 隐藏 URL 输入框
    'ext' => 'png|jpg|jpeg|ico',         // 允许的文件扩展名
    'number' => 'one',                   // one 单张；other 多选
    'placeholder' => '图片地址',
    'tip' => '建议 200x200 像素',
    'required' => false,
    'class' => 'mb10',
]); ?>
```

`type` 参数可选值：

| 值              | 效果                          |
|----------------|-----------------------------|
| `'hidden'`（默认） | 隐藏的 URL 输入框 + 上传按钮 + 预览编辑按钮 |
| `'input'`      | 可见的文本输入框 + 上传按钮（可手动输入URL）   |
| `'text'`       | 只显示上传按钮，点击后从文件管理器选择         |

### 5.2 上传按钮的 JS 初始化

```phtml
<script>
    admin.upload.run();  // 激活所有上传按钮
</script>
```

### 5.3 完整的上传示例

```phtml
<div class="layui-form">
    <?php $vv->layuiHtml()->upload('封面图片', 'cover', [
        'value' => $vv->pick('cover'),
        'type' => 'hidden',
    ]); ?>

    <!-- 其它表单项 -->

    <div class="layui-form-item text-center">
        <button type="submit" class="layui-btn layui-btn-normal" lay-submit>确认</button>
    </div>
</div>
<?php $vv->layui()->addWindowConfig([])->footer(); ?>
<script>
    admin.upload.run();
    admin.form.submitFirst(function () {
        admin.iframe.closeFromParent(true);
    });
</script>
```

---

## 6. 表单控件速查

| 控件类型       | 代码片段                                                                                                             |
|------------|------------------------------------------------------------------------------------------------------------------|
| **文本框**    | `<input type="text" name="title" class="layui-input" value="<?= $vv->pick('title') ?>">`                         |
| **必填文本框**  | 加 `lay-verify="required" lay-reqtext="请填写标题"`                                                                    |
| **数字**     | `<input type="number" name="sort" class="layui-input" value="<?= $vv->pick('sort', 0) ?>">`                      |
| **文本域**    | `<textarea name="remark" class="layui-textarea"><?= $vv->pick('remark') ?></textarea>`                           |
| **下拉框**    | `<select name="status"><option value="1" <?= $vv->pick('status')==1?'selected':'' ?>>启用</option></select>`       |
| **单选**     | `<input type="radio" name="sex" value="m" title="男" <?= $vv->html()->pickCompare('sex','checked','m') ?>>`       |
| **开/关**    | `<input type="checkbox" name="valid" lay-skin="switch" title="是                                                  |否" <?= $vv->html()->pickCompare('valid','checked') ?>>` |
| **多选组**    | `<input type="checkbox" name="ids[1]" lay-skin="primary" title="选项A" <?= in_array(1,$selected)?'checked':'' ?>>` |
| **文件上传**   | `<?php $vv->layuiHtml()->upload('头像','avatar',['value'=>$vv->pick('avatar')]); ?>`                               |
| **日期+时间**  | `<input type="text" id="dt" name="dt" class="layui-input">` + `admin.date.renderDatetime('dt')`                  |
| **仅日期**    | `<input type="text" id="d" name="d" class="layui-input">` + `admin.date.renderDate('d')`                         |
| **图标的隐藏值** | `<?php $vv->layuiHtml()->icon(['value' => $vv->pick('icon')]); ?>` + `$vv->layuiHtml()->iconJs()`                |
| **辅助提示**   | `<div class="layui-form-mid layui-text-em">提示文字</div>` 或 `<div class="hint">提示</div>`                            |

---

## 7. JavaScript 交互约定

### 7.1 弹窗操作

```javascript
// 打开编辑/添加弹窗
admin.iframe.open(prefix + '/add', {title: '添加记录'});
admin.iframe.open(prefix + '/edit?id=' + id, {title: '编辑记录'});

// 弹窗中提交后关闭并刷新父页面
admin.form.submitFirst(function () {
    admin.iframe.closeFromParent(true);  // true = 需要父页面刷新
});

// 自定义提交 URL
admin.form.submitFirst(
    function () { admin.iframe.closeFromParent(true); },
    function (data) {
        // 提交前修改 data 的时机
        data.extra_field = 'xxx';
        return data;
    }
);
```

### 7.2 AJAX 操作

```javascript
// 通用 AJAX 请求，自动处理成功/失败提示
admin.ajax.get({url: prefix + '/list'}, function (res) {
    // res.data, res.msg, res.code
});

admin.ajax.post({
    url: prefix + '/save',
    data: {id: 1, title: 'hello'},
});

// 确认对话框
admin.layer.confirm('确定要删除吗?', function () {
    admin.ajax.post({url: prefix + '/delete', data: {id: 1}});
});
```

### 7.3 行操作事件（row-action 模板）

```javascript
// 方式一：使用 addLayEventActions() — 自动处理 edit/remove
// 方式二：自定义事件
layui.treeTable.on('tool(tableId)', function (obj) {
    const id = obj.data.id;
    switch (obj.event) {
        case 'edit':
            admin.iframe.open(prefix + '/edit?id=' + id, {title: '编辑'});
            break;
        case 'remove':
            admin.layer.confirm('确定删除?', function () {
                admin.ajax.post({url: prefix + '/delete', data: {id}}, function () {
                    obj.del();
                });
            });
            break;
    }
});
```

### 7.4 工具栏事件（toolbar 模板）

```javascript
layui.util.on('lay-on', {
    refresh: function () {
        inst.reloadData();  // 刷新树表格
    },
    create: function () {
        admin.iframe.open(prefix + '/add', {title: '添加'});
    },
    batchDelete: function () {
        // 批量删除
    },
});
```

---

## 8. 完整示例

假设要为 `Product` 模型（字段：title、status、sort、remark、cover）快速编写视图，步骤如下：

### 8.1 add.phtml

```phtml
<?php require_once __DIR__ . '/edit.phtml'; ?>
```

### 8.2 edit.phtml

```phtml
<?php /** @var \App\Modules\tao\Helper\MyMvcHelper $vv */ ?>
<form class="layui-form">

    <?php $vv->layuiHtml()->upload('封面图片', 'cover', [
        'value' => $vv->pick('cover'),
    ]); ?>

    <div class="layui-form-item">
        <label class="layui-form-label required">产品名称</label>
        <div class="layui-input-inline">
            <input type="text" name="title" class="layui-input"
                   lay-verify="required" lay-reqtext="请输入产品名称"
                   placeholder="请输入产品名称"
                   value="<?php echo $vv->pick('title') ?>">
        </div>
    </div>

    <div class="layui-form-item">
        <label class="layui-form-label">排序</label>
        <div class="layui-input-inline">
            <input type="number" name="sort" class="layui-input"
                   value="<?php echo $vv->pick('sort', 0) ?>">
        </div>
    </div>

    <div class="layui-form-item">
        <label class="layui-form-label">状态</label>
        <div class="layui-input-inline">
            <select name="status">
                <option value="1" <?php echo $vv->pick('status', 1) == 1 ? 'selected' : '' ?>>启用</option>
                <option value="2" <?php echo $vv->pick('status', 1) == 2 ? 'selected' : '' ?>>禁用</option>
            </select>
        </div>
    </div>

    <div class="layui-form-item layui-form-text">
        <label class="layui-form-label">备注</label>
        <div class="layui-input-block">
            <textarea name="remark" class="layui-textarea"
                      placeholder="备注信息"><?php echo $vv->pick('remark') ?></textarea>
        </div>
    </div>

    <div class="hr-line"></div>
    <div class="layui-form-item text-center">
        <button type="submit" class="layui-btn layui-btn-normal layui-btn-sm" lay-submit>确认</button>
        <button type="reset" class="layui-btn layui-btn-primary layui-btn-sm">重置</button>
    </div>
</form>
<?php $vv->layui()->addWindowConfig([])->footer(); ?>
<script>
    admin.upload.run();
    admin.form.submitFirst(function () {
        admin.iframe.closeFromParent(true);
    });
</script>
```

### 8.3 index.phtml

```phtml
<?php /** @var \App\Modules\tao\Helper\MyMvcHelper $vv */ ?>

<fieldset class="table-search-fieldset layui-hide" id="table-search">
    <legend>条件搜索</legend>
    <form class="layui-form layui-form-pane form-search">
        <div class="layui-form-item layui-inline">
            <label class="layui-form-label">状态</label>
            <div class="layui-input-inline">
                <select name="status">
                    <option value="">全部</option>
                    <option value="1">启用</option>
                    <option value="2">禁用</option>
                </select>
            </div>
        </div>
        <div class="layui-form-item layui-inline">
            <label class="layui-form-label">产品名称</label>
            <div class="layui-input-inline">
                <input name="title" placeholder="搜索名称" class="layui-input">
            </div>
        </div>
        <div class="layui-form-item layui-inline">
            <a class="layui-btn layui-btn-normal layui-btn-sm" lay-submit>搜索</a>
            <button type="reset" class="layui-btn layui-btn-primary layui-btn-sm">重置</button>
        </div>
    </form>
</fieldset>

<script type="text/html" id="toolbar">
    <div class="layui-table-tool-temp">
        <button class="layui-btn layui-btn-sm" lay-on="refresh"><i class="fa fa-refresh"></i></button>
        <button class="layui-btn layui-btn-normal layui-btn-sm" lay-on="create"><i class="fa fa-plus"></i>添加</button>
        <button class="layui-btn layui-btn-sm layui-btn-danger" lay-on="batchDelete"><i class="fa fa-trash-o"></i>删除</button>
    </div>
</script>

<script type="text/html" id="row-action">
    <div class="layui-btn-container">
        <a class="layui-btn layui-btn-xs" lay-event="edit">编辑</a>
        <a class="layui-btn layui-btn-xs layui-btn-danger" lay-event="remove">删除</a>
    </div>
</script>

<table id="table" class="layui-hide"></table>

<?php $vv->layui()->addWindowConfig([])->footer(); ?>
<script>
    const prefix = '<?php echo $vv->urlModule("tao/admin.product", false) ?>';
    admin.table.with({url: prefix})
        .render({
            toolbar: '#toolbar',
            cols: [[
                {type: 'checkbox'},
                {field: 'id', title: 'ID', width: 50},
                {field: 'cover', title: '封面', width: 70, templet: admin.table.image},
                {field: 'title', title: '产品名称'},
                {field: 'sort', title: '排序', width: 80, edit: 'text'},
                {field: 'status', title: '状态', width: 85, templet: admin.table.switch},
                {field: 'created_at', width: 150, title: '创建时间', templet: admin.table.humanTime},
                {title: '操作', width: 140, toolbar: '#row-action'}
            ]],
        })
        .addPostSwitch()
        .addPostEditText()
        .addLayOnActions()
        .addLayEventActions();
</script>
```

---

## 附：视图文件管理约定速查

| 步骤     | 文件                                                                  |
|--------|---------------------------------------------------------------------|
| 列表页    | `views/layui/admin/{entity}/index.phtml`                            |
| 新增页    | `views/layui/admin/{entity}/add.phtml`（`require_once 'edit.phtml'`） |
| 编辑页    | `views/layui/admin/{entity}/edit.phtml`                             |
| 行操作按钮  | `id="row-action"` 的 `laytpl` 模板                                     |
| 工具栏按钮  | `id="toolbar"` 的 `laytpl` 模板                                        |
| 搜索区    | `id="table-search"` 的 `fieldset`                                    |
| 表格 JS  | `admin.table.with({url: prefix}).render(...)`                       |
| 弹窗提交关闭 | `admin.form.submitFirst(() => admin.iframe.closeFromParent(true))`  |
