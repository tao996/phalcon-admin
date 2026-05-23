# 代码生成工具设计

## 一、概述

生成 CRUD 模块所需的 Model、Controller、View 代码。用户选择一张数据表，配置字段属性，一键生成符合项目规范的文件。

### 生成的产出

| 文件 | 路径规则                                                   | 说明 |
|---|--------------------------------------------------------|---|
| Model | `{module}/Models/{name}.php`                           | 继承 `BaseModel`，含字段属性、SoftDelete 可选 |
| Controller | `{module}/Controllers/{prefix}/{name}Controller.php` | 继承 `BaseController`，满足常规 CRUD |
| Index View | `{module}/views/layui/{prefix}/{name}/index.phtml`   | Layui 列表页 |
| Edit View | 视需要而定（默认无，因为编辑走弹窗）                                     | — |
| JS | 合并在 index.phtml 中，不单独生成                                | — |

---

## 二、操作流程（三步）

### Step 1 — 选择表和模块

![step1](../images/devtool-step1.png)

```
┌─────────────────────────────────────────────┐
│  代码生成工具                                │
│                                            │
│  选择模块: [下拉: tao / yihe / …… ]          │
│  选择数据表: [下拉: 从 information_schema ]  │
│  选择模块 : [下拉: tao / yihe / ...]         │
│  控制器前缀: [________]  (admin / user / …) │
│  模型名:    [________]  (PascalCase)         │
│                                              │
│  生成选项:                                    │
│  ☑ 软删除 (SoftDelete trait)                 │
│  ☑ 时间戳 (created_at / updated_at)          │
│  ☐ 搜索控制器(indexActionQueryBuilder)        │
│                                              │
│  [下一步：配置字段]                            │
└─────────────────────────────────────────────┘
```

**输入字段**：

| 字段 | 来源 | 说明 |
|---|---|---|
| 模块 | 扫描 `src/App/Modules/` 目录名 | 必选 |
| 数据表 | `SHOW TABLES` 或 `information_schema.TABLES` | 必选 |
| 生成目录名 | 默认 = 表名（去前缀后的 snake_case） | 自定义 |
| 模型名 | 默认 = 目录名转 `PascalCase` | 自定义 |
| 控制器前缀 | `admin` / `user` / 空 | 默认 `admin` |
| 软删除 | 勾选则给 Model 加 `use SoftDelete` + `deleted_at` 字段过滤 | 默认开启 |
| 时间戳 | 勾选则设 `created_at` / `updated_at` 字段 | 默认开启 |

---

### Step 2 — 配置字段属性

表选择后，从 `information_schema.COLUMNS` 读取字段列表，渲染为表格：

```
┌──────┬──────────┬──────┬──────────┬──────────┬──────────┬───────────────────┐
│ 选中  │ 字段名    │ 类型  │ 标签      │ 列表显示   │ 可搜索    │ 可编辑  │  表单组件  |
├──────┼──────────┼──────┼──────────┼──────────┼──────────┼────────┤───────────┤
│ ☑    │ id       │ int  │ ID      │ ☑        │ ☐        │ ☐      │ 无       |
│ ☑    │ name     │ vc   │ 名称     │ ☑        │ ☑        │ ☑     │ input     |
│ ☑    │ status   │ int  │ 状态     │ ☑        │ ☐        │ ☑     │ switch    |
│ ☑    │ sort     │ int  │ 排序     │ ☑        │ ☐        │ ☑     │ number    |
│ ☑    │ remark   │ txt  │ 备注     │ ☑        │ ☐        │ ☑     │ textarea  |
│ ☑    │ created  │ int  │ 创建时间  │ ☑        │ ☐        │ ☐     │ 无        |
└──────┴──────────┴──────┴──────────┴──────────┴──────────┴────────┴──────────┘
                                                                     [生成代码]
```


**每行的配置列**：

| 配置列    | 含义                                     | 影响                                                       |
|--------|----------------------------------------|----------------------------------------------------------|
| 选中     | 此字段是否纳入生成（未选中的字段不在 Model/Controller 中出现） | —                                                        |
| 标签     | 中文显示名，用于 table col title / form label  | View 中的 title                                            |
| 列表显示   | 在 Index 表格中显示此列                        | `templet` / `field`                                      |
| 可搜索    | 在 indexAction 中支持 keyword 模糊搜索         | `$queryBuilder->like(...)`                               |
| 可编辑    | 支持辅助在线编辑（通过 modifyAction）              | `$this->allowModifyFields` 或 `$this->appendModifyFields` |
| 表单编辑   | 在添加/编辑表单中出现                            | 默认勾选；主键不勾选                                               |
| 必填(表单) | 表单验证时为必填                               | `lay-verify="required"`                                  |
| 类型映射   | 当前检测到的类型自动归类，可修改                       | 见下方映射表                                                   |
| 表单组件   | 使用哪个表单组件来显示当前属性                        | 见下方映射表                                                   |

**表单组件映射**：

可参考 [表单组件文档](../layui_phtml.md)

* `layui` 内置的表单组件 `input`, `switch`, `textare`, `select`, `checkbox`, `radio`, `date`, `datetime`
* `tao` 封装的组件
  * `file upload` 文件上传组件
  * `data select` 数据选择组件


**自动类型映射**：

| 列类型（MySQL） | 映射为 | 表单控件 | View 显示 |
|---|---|---|---|
| `int` / `tinyint` / `smallint` | int | input number | 数字 / switch 二选一 |
| `varchar` / `char` | string | input text | 文本 |
| `text` / `mediumtext` / `longtext` | string | textarea | 文本（可截断） |
| `decimal` / `float` / `double` | float | input number | 数字 |
| `date` | string | laydate | 日期 |
| `datetime` | string | laydate datetime | 日期时间 |
| `timestamp` / `int(10)` 时间戳 | int | laydate datetime | `humanTime` |
| `tinyint(1)` / `tinyint` + 注释含「状态」 | int | switch | `admin.table.switch` |

**特殊字段自动识别**：

| 字段名 | 行为 |
|---|---|
| `id` | 主键，不可编辑，不可搜索，列表显示 |
| `created_at` / `updated_at` | 自动时间戳，不显示在表单中 |
| `deleted_at` | 仅当开启软删除时显示，不可编辑 |
| `status` | 自动映射为 `switch` 控件，添加到 `$allowModifyFields` |
| `sort` | 自动映射为数字输入，添加到 `$allowModifyFields`，列表可编辑 `edit:'text'` |

---

### Step 3 — 生成 & 预览

点击「生成代码」后，后端生成所有文件内容，以 diff 形式展示：

```
┌─────────────────────────────────────────────┐
│  预览生成结果                                │
│                                              │
│  📄 Models/Category.php                      │
│  ┌─────────────────────────────────────────┐│
│  │+ <?php                                  ││
│  │+ namespace App\Modules\tao\Models;      ││
│  │+ ...                                    ││
│  └─────────────────────────────────────────┘│
│                                              │
│  📄 Controllers/admin/CategoryController.php│
│  ┌─────────────────────────────────────────┐│
│  │+ ...                                    ││
│  └─────────────────────────────────────────┘│
│                                              │
│  📄 views/layui/admin/category/index.phtml  ││
│  ┌─────────────────────────────────────────┐│
│  │+ ...                                    ││
│  └─────────────────────────────────────────┘│
│                                              │
│  生成目录: src/App/Modules/tao/             │
│                                              │
│  [确认生成] [返回修改]                       │
└─────────────────────────────────────────────┘
```

确认后写入文件，并提示下一步操作（如刷新 RBAC、添加菜单）。

---

## 三、后端 MVC 生成逻辑

### 3.1 Model 模板

```php
<?php

namespace App\Modules\{module}\Models;

use App\Modules\{module}\Base{module}Model;
{softDeleteImport}

class {name} extends Base{module}Model
{
    {softDeleteTrait}

    // 字段声明（仅 selected 的字段）
    public {type} ${field} = {default};
    ...

    public function tableTitle(): string
    {
        return '{chineseName}';
    }

    {beforeSaveBlock}
}
```

**规则**：
- 类型从类型映射中取 PHP 类型（`string` / `int` / `float`）
- 默认值：`int` → `0`，`string` → `''`，`float` → `0.0`
- 字段注释追加在 `/** ... */` 上方
- `beforeSave()` 仅在存在必填或唯一字段时生成

### 3.2 Controller 模板

```php
<?php

namespace App\Modules\{module}\Controllers\{prefix};

use App\Modules\{module}\BaseController;
use App\Modules\{module}\Models\{name};
use Phax\Db\QueryBuilder;

/**
 * @rbac ({title:'{chineseName}管理'})
 * @property {name} $model
 */
class {name}Controller extends BaseController
{
    protected string $htmlTitle = '{chineseName}';

    public function afterInitialize(): void
    {
        $this->model = new {name}();
    }

    {indexQueryColumns}

    {allowModifyFields}

    {indexActionQueryBuilderBlock}

    {addAction?}

    {editAction?}
}
```

**规则**：
- `$indexQueryColumns`：取所有 `列表显示` 为 true 的字段，用逗号连接
- `$allowModifyFields`：取所有 `可编辑` 为 true 的字段
- `indexActionQueryBuilder`：取所有 `可搜索` 为 true 的字段，生成 `->like(...)` / `->int(...)` 链
- 如果某字段在 DB 中有唯一索引，自动在 Controller 中生成唯一性校验
- `addAction` / `editAction` 只在存在 `表单编辑` 为 true 的字段且与 Model 字段列表有差异时才生成覆写（`saveWhiteList`）

### 3.3 View 模板

参考 `src/App/Modules/tao/views/layui/admin/role/index.phtml` 的格式：

```html
<fieldset class="table-search-fieldset layui-hide" id="table-search">
    <form class="layui-form ..." lay-filter="form-search">
        {searchFields}
        <a class="layui-btn ..." lay-submit>搜索</a>
        <button type="reset" ...>重置</button>
    </form>
</fieldset>

<script type="text/html" id="toolbar">
    <div class="layui-table-tool-temp">
        <button lay-on="refresh">刷新</button>
        <button lay-on="create">添加</button>
        <button lay-on="batchDelete">删除</button>
    </div>
</script>

<script type="text/html" id="row-action">
    <a lay-event="edit">编辑</a>
    <a lay-event="remove">删除</a>
</script>

<table id="table"></table>

<script>
    const prefix = '...';
    admin.table.with({url: prefix})
        .render({
            toolbar: '#toolbar',
            cols: [[
                {type: 'checkbox'},
                {field: 'id', title: 'ID', width: 50},
                {tableColumns}
                {actionColumn}
            ]],
        })
        .addPostSwitch()
        .addPostEditText()
        .addLayOnActions()
        .addLayEventActions();
</script>
```

**搜索字段渲染规则**：
- `varchar` 映射为 `<input>` 关键词搜索
- `int` 如果 options 中有枚举值（status 等）映射为 `<select>`
- `date` / `datetime` 映射为范围搜索 `search:'range'`

---

## 四、控制器与数据库接口

代码生成本身作为一个控制器 `DevtoolController`：

| 方法 | URL | 说明 |
|---|---|---|
| `GET /devtool/index` | 工具首页，展示配置表单 | Step 1 页面 |
| `GET /devtool/tables?module=xxx` | AJAX：获取指定模块的数据库表列表 | 从 `information_schema.TABLES` 读取 |
| `GET /devtool/columns?table=xxx` | AJAX：获取表字段详情 | 从 `information_schema.COLUMNS` 读取 |
| `POST /devtool/preview` | 预览生成的代码（不写入） | Step 3 预览 |
| `POST /devtool/generate` | 确认生成，写入文件 | 最终生成 |

---

## 五、需要处理的问题

| # | 问题 | 处理方式 |
|---|---|---|
| 1 | 生成的 Controller 需要注册到 RBAC 中 | 自动调用 `NodeService::createByController($ctrlClass)` 或提示用户手动刷新 |
| 2 | 生成的文件名冲突 | 预览页检查目标路径是否已存在文件，存在则标红警告 |
| 3 | 不同模块的 BaseModel 不同（`BaseTaoModel`、`BaseYiheModel`） | 扫描模块下的 BaseModel 文件名自动匹配 |
| 4 | 外键字段自动识别 | 从 `information_schema.KEY_COLUMN_USAGE` 读取，标记为 `select` 控件，不生成编辑 |
| 5 | 枚举/字典字段 | 如果字段注释包含 `{enum}` 标记，或 t 表中有 dictionary 表，自动映射为 `<select>` |
