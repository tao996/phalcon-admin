# AI-First Guide: Generating Controllers & Views for Models

This guide documents the structural patterns in the `demo` and `tao` modules so an AI (or developer) can generate new model+controller+view triples consistent with the existing codebase.

---

## 1. URL Routing & Module Layout

| Concept | Pattern | Example |
|---|---|---|
| Module prefix | `/m/{module}/{controller}/{action}` | `/m/tao/admin.user/index` |
| API mode | Prefix `/api/` | `/api/m/tao/admin.user/index` |
| Controller namespace | `App\Modules\{module}\Controllers` | `App\Modules\tao\Controllers` |
| Subdirectory controller | Dot in name maps to subdir | `admin/user/UserController.php` → `admin.user` |
| A0 submodule | `A0/{name}/Controllers/...` | `A0/app/Controllers/admin/InfoController.php` |
| A0 submodule URL | `/m/{module}.{submodule}/{controller}/{action}` | `/m/tao.app/admin.info/index` |

Route mapping is handled by `\Phax\Foundation\Route` and `\Phax\Support\Router`. The `$modulePrefix` is `/m/`, the `$projectPrefix` is `/p/`.

---

## 2. Model Patterns

### Demo Module (`App\Modules\demo\Models\`)

Models extend `DemoBaseModel` → `\Phax\Mvc\Model`. Table prefix is `demo_`.

```php
namespace App\Modules\demo\Models;
use App\Modules\demo\DemoBaseModel;

class Article extends DemoBaseModel
{
    public int $id = 0;
    public int $user_id = 0;
    public string $title = '';
}
```

Key practices visible in the code:

- **Declare public typed properties** with defaults — Phalcon hydrates these from DB columns. Every column needs a matching property.
- **Relations** use Phax custom methods: `$this->hasManyPhx(Model::class)`, `$this->hasOnePhx(Model::class)`, `$this->hasManyToManyPhx(Model::class, Pivot::class)`.
- **Soft delete**: add `use Phax\Traits\SoftDelete;` to the model. This adds `deleted_at` column handling.
- **Auto timestamps**: set `protected string|bool $autoWriteTimestamp = 'timestamp';` to auto-manage `created_at`/`updated_at`. Set to `false` to disable.
- **Accessors**: `getFieldNameAttr()` — return a computed value, accessed as `$model->field_name`.
- **Mutators**: `setFieldNameAttr($value)` — transform the value before assignment (only triggers when property isn't explicitly declared).
- **Events**: override `beforeCreate()`, `beforeSave()`, `beforeDelete()`, `afterDelete()`, etc.

### Tao Module (`App\Modules\tao\Models\`)

Models extend `BaseTaoModel` → `\Phax\Mvc\Model`. Table prefix is `tao_` (from `Config::TABLE_PREFIX`).

```php
namespace App\Modules\tao\Models;
use App\Modules\tao\BaseTaoModel;

class SystemUser extends BaseTaoModel
{
    use SoftDelete;
    public int $status = 1;
    public string $nickname = '';
    // ...
}
```

`BaseTaoModel` pre-declares `id`, `created_at`, `updated_at`, `deleted_at` — do not repeat them.

---

## 3. Controller Patterns

### Demo Module (simple CRUD or feature controllers)

Extend `\Phax\Mvc\Controller`. Actions return an **array** (auto-assigned to view variables) or `void` (use `$this->view->setVars()`).

```php
/**
 * @rbac ({title:'Form Demo'})
 */
class TodoController extends Controller
{
    /**
     * @rbac ({title:'List'})
     */
    public function listAction(): array
    {
        return ['name' => 'todo list'];
    }
}
```

- Return `['key' => $value]` → view accesses via `$vv->pick('key')`.
- `@rbac` annotations on class (module-level title) and each action. `close:1` hides from menu.
- Subdirectory controllers match URL segments: `Controllers/db/TestController.php` → action `helloAction` → URL `/m/demo/db.test/hello`.

### Tao Module (admin CRUD)

Extends `BaseController` → `BaseRbacController` → `BaseResponseController` → `\Phax\Mvc\Controller`.

The `BaseController` provides **automatic** `index`, `add`, `edit`, `modify`, `delete` actions. To use them, a minimal controller is:

```php
namespace App\Modules\tao\Controllers\admin;

/**
 * @rbac ({title:'应用信息'})
 * @property AppInfo $model
 */
class InfoController extends \App\Modules\tao\BaseController
{
    protected string $htmlTitle = '应用信息';
    protected array|string $superAdminActions = '*';

    public function afterInitialize(): void
    {
        $this->model = new AppInfo();
    }
}
```

That gives you: data list, add form, edit form, inline modify (status/sort), and delete — all wired.

**Customization hooks** (override selectively):

| Method | Purpose |
|---|---|
| `indexActionQueryBuilder($qb)` | Add WHERE/LIKE filters from search params |
| `indexActionGetResult($count, $qb)` | Transform result rows (join related data) |
| `indexQueryColumns` / `indexHiddenColumns` | Column whitelist/blacklist for list API |
| `indexOrder` | Default sort (default: `'id desc'`) |
| `beforeModelSaveAssign($data)` | Modify POST data before saving |
| `saveWhiteList` | Fields allowed during `$model->assign()` |
| `allowModifyFields` | Fields allowed in `modifyAction` (inline edit) |
| `afterModelChange($action)` | Hook after add/edit/delete/modify |
| `deleteActionBefore($qb, $ids)` | Validate before delete |
| `checkModelActionAccess($model)` | Ownership/permission check |

**Access control properties** (on `BaseRbacController`):

| Property | Effect |
|---|---|
| `openActions` | Actions accessible without login (string `'*'` = all) |
| `userActions` | Actions accessible to any logged-in user |
| `superAdminActions` | Actions restricted to super admin |
| `enableActions` | Whitelist of allowed actions |
| `disableActions` | Blacklist of denied actions |
| `disableUpdateActions` | Block all add/edit/modify/delete |
| `updateActions` | Default: `['add', 'edit', 'modify', 'delete']` |

**API vs Web detection**: Controllers auto-detect. API requests return JSON via `success()`, `error()`, `successPagination()`. Web requests render views.

---

## 4. View Patterns

### Demo Module Views

Location: `views/{controller}/{action}.phtml` (relative to module root).

```phtml
<!-- views/todo/list.phtml -->
<?php
/** @var \Phax\Helper\MyMvc $vv */
?>
<h5>todo list: <span><?php echo $vv->pick('name') ?></span></h5>
```

- `$vv->pick('key')` reads the value returned by the controller action.
- `$this` is `\Phalcon\Mvc\View\Engine\AbstractEngine`.
- Module-level layout: `views/layouts/index.phtml` — wrap content with `<?php echo $this->getContent(); ?>`.
- No theme system — views are at the fixed path.

### Tao Module (Layui Admin) Views

Location: `views/{theme}/{controller}/{action}.phtml` where `theme` defaults to `layui`.

Three view types:

**1. List view** (`index.phtml`) — search form + toolbar template + table:

```phtml
<?php /** @var \App\Modules\tao\Helper\MyMvcHelper $vv */ ?>
<fieldset class="table-search-fieldset" id="table-search">
    <legend>条件搜索</legend>
    <form class="layui-form layui-form-pane form-search">
        <!-- search fields -->
        <div class="layui-form-item layui-inline">
            <label class="layui-form-label">状态</label>
            <div class="layui-input-inline">
                <select name="status"><option value="">全部</option></select>
            </div>
        </div>
    </form>
</fieldset>

<script type="text/html" id="toolbar">
    <div class="layui-table-tool-temp">
        <button class="layui-btn layui-btn-sm" lay-on="refresh"><i class="fa fa-refresh"></i></button>
        <button class="layui-btn layui-btn-normal layui-btn-sm" lay-on="create"><i class="fa fa-plus"></i>添加</button>
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
    const prefix = '<?php echo $vv->urlModule("tao.module/controller", false) ?>';
    admin.table.with({url: prefix}).render({toolbar: '#toolbar', cols: [[
        {type: 'checkbox'},
        {field: 'id', title: 'ID', width: 50},
        {field: 'status', title: '状态', templet: admin.table.switch},
        {title: '操作', toolbar: '#row-action'},
    ]}).addPostSwitch().addLayOnActions().addLayEventActions();
</script>
```

**2. Form view** (`edit.phtml`, also used by `add.phtml` via `require_once`) — uses Layui form components:

```phtml
<form class="layui-form">
    <div class="layui-form-item">
        <label class="layui-form-label">标题</label>
        <div class="layui-input-inline">
            <input type="text" name="title" class="layui-input"
                   value="<?php echo $vv->pick('title') ?>">
        </div>
    </div>
    <!-- more fields -->
    <div class="hr-line"></div>
    <div class="layui-form-item text-center">
        <button type="submit" class="layui-btn" lay-submit>确认</button>
    </div>
</form>
<?php $vv->layui()->addWindowConfig([])->footer(); ?>
<script>admin.form.submitFirst(function(){ admin.iframe.closeFromParent(true) })</script>
```

The `add.phtml` is typically just `<?php require_once __DIR__ . '/edit.phtml'; ?>`.

**Key helpers in views:**

| Expression | Purpose |
|---|---|
| `$vv->pick('field')` | Output field value from controller return array |
| `$vv->urlModule("tao.module/ctrl", false)` | Generate URL prefix for AJAX calls |
| `$vv->html()->pickCompare('field', 'checked')` | Output `checked` if field is truthy (checkboxes/switches) |
| `$vv->layui()->addWindowConfig([])->footer()` | Required footer for admin modals |
| `admin.table.with({url})` | Layui table binding JS helper |

---

## 5. Step-by-Step: Generate a New CRUD Module

Given a database table `tao_product` (with fields: `id, title, status, sort, remark, created_at, updated_at`):

### Step 1: Create the Model

```
src/App/Modules/tao/Models/Product.php
```

```php
namespace App\Modules\tao\Models;
use App\Modules\tao\BaseTaoModel;

class Product extends BaseTaoModel
{
    public string $title = '';
    public int $status = 1;
    public int $sort = 0;
    public string $remark = '';
}
```

### Step 2: Create the Controller

```
src/App/Modules/tao/Controllers/admin/ProductController.php
```

```php
namespace App\Modules\tao\Controllers\admin;

/**
 * @rbac ({title:'产品管理'})
 * @property Product $model
 */
class ProductController extends \App\Modules\tao\BaseController
{
    protected string $htmlTitle = '产品管理';

    public function afterInitialize(): void
    {
        $this->model = new Product();
    }

    // Optional: add search filters
    protected function indexActionQueryBuilder(\Phax\Db\QueryBuilder $qb): void
    {
        $qb->int('status', $this->request->getQuery('status', 'int'));
        $qb->like('title', $this->request->getQuery('title', 'string'));
    }
}
```

### Step 3: Create the Views

List view: `src/App/Modules/tao/views/layui/admin/product/index.phtml`

Form view: `src/App/Modules/tao/views/layui/admin/product/edit.phtml`

Add view: `src/App/Modules/tao/views/layui/admin/product/add.phtml`

For the add view, simply `require_once __DIR__ . '/edit.phtml';`.

### Step 4: Register Migration (optional)

Add migration data files under `data/migration/{version}/`.

---

## 6. A0 Submodule Pattern (Advanced)

For self-contained sub-apps within a module (e.g., `tao` module has `A0/app`, `A0/cms`, `A0/open`):

```
src/App/Modules/tao/A0/{appname}/
├── Controllers/
│   └── admin/
│       └── InfoController.php
├── Models/
│   └── AppInfo.php
├── Services/
│   └── AppInfoService.php
└── views/
    └── layui/
        └── admin/
            └── info/
                ├── index.phtml
                ├── add.phtml
                └── edit.phtml
```

Controllers extend the **same `BaseController`** from tao module. URL path uses dot notation: `/m/tao.app/admin.info/index` → module=`tao.app`, controller=`admin.info`, action=`index`.

---

## 7. URL Reference for New Files

| File | Purpose | Path (relative to `src/`) |
|---|---|---|
| Model class | DB mapping | `App/Modules/{module}/Models/{Name}.php` |
| Controller class | HTTP logic | `App/Modules/{module}/Controllers/{subdir}/{Name}Controller.php` |
| List view | Data table | `App/Modules/{module}/views/{theme}/{controller-dir}/index.phtml` |
| Add form | Create record | `App/Modules/{module}/views/{theme}/{controller-dir}/add.phtml` |
| Edit form | Update record | `App/Modules/{module}/views/{theme}/{controller-dir}/edit.phtml` |
| Module registration | Declare module | `App/Modules/{module}/Module.php` |
| Base model | Shared table prefix | `App/Modules/{module}/{ModuleName}BaseModel.php` |
