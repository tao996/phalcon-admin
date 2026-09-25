# AGENTS.md — App\Modules 模块开发约定

本文件约束 `src/App/Modules/{demo,tao,yihe,worksheet}` 下**模型 / 控制器 / 视图**的写法，
以及**对外接口的基类与鉴权约定（§2.1）**、**接口测试（§6）**。
**范本：`yihe/Car`**（`Models/Car.php`、`Controllers/CarController.php`、`views/layui/car/{index,edit,add}.phtml`）。
**数据访问：ORM 优先（见 §5）**；**接口测试见第 6 节**。

运行命令都在 `src/` 下执行；`src/App/Modules/*` 被 .gitignore 忽略，改动不会出现在 `git status`。

## 0. 一次 CRUD 需要动的文件

| 文件 | 职责 |
|---|---|
| `Models/Xxx.php` | 字段类型、字典常量、表名字段声明（**不放业务逻辑**） |
| `Controllers/XxxController.php` | 继承 `tao\BaseController`，只重写钩子 + 少量自定义 action |
| `views/layui/xxx/index.phtml` | layui 表格 + 搜索 + 行操作 |
| `views/layui/xxx/edit.phtml` | 表单（增改共用） |
| `views/layui/xxx/add.phtml` | 只有一行 `include __DIR__ . '/edit.phtml';` |
| 可选 `move.phtml` / `select.phtml` | 只读页、自定义动作页、弹窗选择页 |

## 1. 模型 `Models/`

```php
use Phax\Mvc\Model;

class BaseYiheModel extends Model
{
    protected string $tablePrefix = 'yihe_'; // 表名前缀

    public int $id = 0;
    public int $created_at = 0; // int(11) default 0 unsigned
    public int $updated_at = 0;
    public int|null $deleted_at = null;
}

class Car extends BaseYiheModel
{
    // protected string $table = 'yihe_car'; // 手动指定表名或由系统自行推导
    
    public string $no = '';        // 强类型 + @var 注释 + 默认值
    public int $type = 0;
    public array $bool2IntColumns = ['status'];
    public array $intColumns = ['type', 'driver_id'];
    public array $floatColumns = ['processing_amount'];

    public const array MAP_TYPE = [self::TYPE_SILT_TRUCK => '淤泥车', /* ... */];
    // 模型使用到的其它常量
    public const int TYPE_SILT_TRUCK = 1;
    public const float CAR_SILT_PRICE = 160;
}
```

- **继承模块基类模型**（`BaseYiheModel` 提供 `tablePrefix='yihe_'` 与 `id/created_at/updated_at/deleted_at`）；新模型**不要**重复声明这些通用字段，`yihe_car` 可由前缀 + 类名推导。
- 每个字段都要：PHP 强类型、`@var` 注释（含中文业务含义）、**给默认值**（`= ''` / `= 0` / `= null` 配 `int|null`，否则 `assign` 会抛 `Cannot assign null to property`）。
- 类型转换**用声明数组**：`intColumns` / `floatColumns` / `bool2IntColumns` / `nullColumns`。
  它们由 `BaseController::beforeModelAssign()` 与 `Model::getAssignData()` 自动消费，**控制器里不要再手写 `(int)`/`(float)`**。
- 枚举与字典用 `const` + `MAP_*` 数组（`MAP_TYPE`），视图与控制器**共用同一份字典**（视图 `json_encode(Car::MAP_TYPE)`），禁止前端硬编码中文。
- 单价、比例等业务常量放模型（如 `CAR_SILT_PRICE` / `CAR_OTHER_PRICE`），供 `TripService` 之类的服务层引用。
- **对外标识与内部主键分离**（只对"有外部调用方"的表，如课件工作区）：内部 `id`（int 自增，**关联表与后台 CRUD 都用它**）＋
  对外 `uuid`（不可猜测，外部接口/下载地址只暴露它）。可让 uuid 内嵌 id 以便 O(1) 解析
  （范本 `worksheet/Services/WorksheetAssignmentService.php`，形如 `asg-20260922-ab12-7`，末段是 id 的 36 进制）；
  **关联表存内部 id**（如 `worksheet_assignment_version.assignment_id` 是 int），`uuid` 只在**接口边界**与
  **文件目录**里出现（范本 `WorksheetAssignmentService::getWithUuid()` / `uuidsByIds()` / `idToUuid()`）。

## 2. 控制器 `Controllers/`

```php
/**
 * @property Car $model
 * @property Car $oldModel
 */
#[RBAC(title: '车辆管理')]
class CarController extends BaseController
{
    protected string $htmlTitle = '车辆管理';
    protected bool $keepOldModel = true;                       // 需要改动前后的模型快照时打开
    protected array|string $userActions = ['search', 'trip', 'select'];  // 普通登录用户可访问的 action

    protected function afterInitialize(): void
    {
        parent::afterInitialize();
        $this->model = new Car();                              // 或 protected string $modelClassName = Car::class;
    }
}
```

- 继承 `App\Modules\tao\BaseController`：`index / add / edit / delete / modify / batchChange` 已实现，含分页、事务、日志、软删除、RBAC 登录检查、`user_id` 归属校验 —— **不要重写这些 action**（确需自建查询时的判断标准见 §5）。
- 类注释必须写 `@property`，供 IDE 与静态分析推断 `$this->model`。
- 权限：类级 `#[RBAC(title: '车辆管理')]`；需要独立授权的动作再单独加 `#[RBAC(title: '车辆迁移')]`。访问范围用 `$openActions`（公开）/`$userActions`（登录即可）/`$superAdminActions`（超管），语义见 `BaseRbacController::rbacInitialize()`。
- **只重写钩子**，不重写 CRUD 流程：

| 钩子 | 用途（Car 中的实例） |
|---|---|
| `actionQuery(QueryBuilder)` | 追加查询条件：`like` / `int` / `between` / `in`；**先调 `parent::actionQuery()`**（默认处理 `status`） |
| `buildIndexResult(int, QueryBuilder)` | 在父类分页结果上**批量**补关联字段：先收集 id → `in('id',$ids)->findColumn(['id','name'],'id')` → 循环回填 `_driver_name`，杜绝 N+1 |
| `beforeEditView(array)` | 渲染编辑页前补数据（`_driver_name`），最后 `return parent::beforeEditView($data)` |
| `beforeModelAssign(array)` | 入库前加工（全角逗号转半角），**先调 parent** 以复用类型转换 + `rules` 校验 |
| `beforeModelSave(bool $add)` | `save()` 前改模型（默认值、派生字段） |
| `validateModelAssign(array)` | 业务校验，失败抛 `BusinessException` |
| `beforeDeleteQuery(QueryBuilder, array $ids)` | 删除前拦截（如「已绑定 N 条出行记录」），抛 `BusinessException`，**不要**把这类校验写进视图 |
| `afterModelChange(string $action)` | `add|edit|delete` 成功后同步派生数据；`edit` 时用 `$this->oldModel` 拿到旧值（`$keepOldModel = true`） |
| `afterBatchDelete(array $ids)` / `beforeBatchChange` / `afterBatchChange` | 批量场景的对应钩子 |

- **自定义 action** 只做「基类没提供的能力」，如 `searchAction`（远程搜索，`return $this->success('', $items)`）、`selectAction`（`return []` 渲染弹窗页）、`moveAction`（数据迁移）。
  - 需要写多张表时用 `Transaction::db(fn)` 包起来；
  - 纯 JSON 小接口（如 `tripAction`）设 `$this->jsonResponse = true;` 后直接返回数组；
  - 成功一律 `$this->success($msg, $data)`，失败抛 `BusinessException` 或 `$this->error(...)`；
  - **不要在控制器里写 SQL**（`$this->db->execute()/fetchOne()/fetchAll()`、`INSERT/UPDATE/SELECT` 字面量）：
    读写一律走模型 ORM（`Model::queryBuilder()->…->find()/findFirstModel()/update()`、`new Model()->assign($data)->save()`），
    聚合/多表用「多次查询 + PHP 组装」，详见 §5。

### 2.1 对外接口（App / 小程序）的基类与鉴权约定

公开 JSON 接口的基类应**继承 tao 的 `BaseController`**（`→ BaseRbacController` → `BaseResponseController`）：

| 声明 | 含义 |
|---|---|
| `$openActions = '*'` 或 `['action1','action2']` | **匿名可访问**（App 读接口） |
| **不进** `$openActions` + `$userActions = '*'` | **登录即可**（不需后台节点授权）；未登录由 `rbacInitialize()` 统一拒绝：`{code:303,msg:'您还没有登录'}`（`AppService::echoJsonData()` 内 `exit`） |
| `$disableUpdateActions = true` | `add/edit/modify/delete` 一律不可访问（对外接口没有后台 CRUD） |

- 业务级权限（如"作者被禁用制作权限"）**就近放在真的需要它的控制器的 `afterInitialize()`**，action 内只留数据级校验（记录归属）：
  ```php
  protected function afterInitialize(): void   // tao BaseController::initialize() 在 rbacInitialize() 之后调用它
  {
      // 本控制器 $userActions='*' ⇒ 登录已保证，可直接取 loginUser()
      if (!AsgReviewService::authorAllowed((int)$this->loginUser()->id)) {
          AppService::echoJsonData($this->error('author_disabled', 403));
      }
  }
  ```
- 响应签名全项目统一 `success(string $msg, mixed $data = null)` / `error($msg, $code = 500)`。
- JSON body / `?data=jsonbody` 解析、`isLogin()` / `loginUser()` 都由基类提供：
  用 `$this->requestData`，不要自己 `json_decode(getRawBody())`。
- **取"当前登录用户"要区分两种语义**：`loginUser()` 内部是 `LoginUserHelper::user()`，**未登录时抛 `BusinessException`**，
  只能用在已登录分支（`$userActions='*'` 的控制器在 `afterInitialize()` / action 内可直接用 `$this->loginUser()->id`）；
  匿名 action 要"未登录返回 0"就用 `isLogin()` 短路（它内部 `try/catch` 返回 false，且会先 `tryGetLoginAuth()` 初始化适配器）：
  `$uid = $this->isLogin() ? (int)$this->loginUser()->id : 0;`。
- 二进制下载： `$this->response->setHeader(...)->setContent(...)->send();exit();`。

范本：`worksheet/WorksheetBaseController.php`（继承 `tao\BaseController`：`$openActions`/`$userActions` 声明矩阵 +
模块专用的包体读取、二进制输出与 CORS；**不覆写** `initialize()` 与响应方法）。

### 2.2 服务层 `Services/`

- **命名**：文件名与类名一律以 `Service` 结尾，并**尽量用 `{ModelName}Service`**
  （`WorksheetAssignmentService` / `WorksheetAssignmentReviewService` / `WorksheetStoreService`；
  `GradingService` / `AiGraderService` 这类纯算法服务不套模型前缀），一个文件一个服务；不要 `Helper`、不要无后缀。
- **方法一律 `static`**：服务不是实例状态的容器 —— DB 走 `AppService::getShared('db')`、配置走 `ConfigService`/
  `getenv()`、路径由 `__DIR__` 推导。控制器里**不要** `new XxxService()`，更不要在控制器里缓存服务实例
  （直接用静态调用：`WorksheetAssignmentReviewService::xxx()`）。
- **优先模型 ORM，不写裸 SQL**：数据访问一律 `Model::queryBuilder()->…->find()/findFirstModel()/update()` 与
  `new Model()->assign($data)->save()`；`$db->execute()/fetchOne()/fetchAll()`、`INSERT/UPDATE/SELECT` 字面量一律不用（见 §5）。
- **返回模型对象**：单条查询返回 `findFirstModel()` 的模型，批量返回 `findModels()`（**模型数组**，不要 `find()` 的行数组）。
  **跨表联合数据返回 DTO**（写法仿 `yihe/Console/DTO/CustomerTableDTO`：public 强类型属性 + 构造 + `toArray()`；
  范本 `worksheet/DTO/WorksheetAssignmentListDTO` / `WorksheetAssignmentStageDTO`），不要返回裸数组；
  只有视图投影（如 `historyForView()` 附展示文案）才返回数组，且需在方法注释里写明结构。
- **确需注入（可替换实现 / 需要构造参数 / 便于测试）时**用"静态门面 + 惰性 DI"，仿 `tao/TaoAppService`：
  ```php
  class WorksheetAppService
  {
      public static function store(): WorksheetStoreService
      {
          return AppService::getLazyService('worksheet.store', fn() => new WorksheetStoreService(/* 或其它实现 */));
      }
  }
  ```
  调用处仍是 `WorksheetAppService::store()->xxx()` 这类静态写法，但实例由容器提供、可被替换。
- 纯函数（判分、字典、标识编解码）都应收在服务里，控制器只做参数校验与响应组装。

> **改基类/覆写方法后必须真正加载一次类**：`php -l` 只查语法，不查继承签名与属性兼容
> （少一个 `use`、覆写签名不兼容、引用了已删除的属性，都只在类加载时 fatal）。两个便宜的检查：
> ```bash
> cd src
> # 1) 实际加载类（走项目 bootstrap，不需要后台在跑）
> php -r "require 'tests/bootstrap.php'; var_dump(class_exists('App\Modules\worksheet\WorksheetBaseController'));"
> # 2) 只列用例：验证命名空间 / 自动加载 / #[Depends] 链（同样不需要后台在跑）
> vendor/bin/phpunit --bootstrap tests/bootstrap.php --no-configuration --list-tests App/Modules/worksheet/tests/PHPUnit
> ```
> 或直接跑一遍第 6 节的接口测试（更推荐）。

## 3. 视图 `views/layui/{controller}/`

视图路径 = `views/` + 控制器 `$theme`（`BaseResponseController::$theme = 'layui'`）+ `{controller}/{action}.phtml`，**模板名必须与 action 同名**（`addAction` 就必须有 `add.phtml`）。

所有模板首行固定：

```php
/** @var \App\Modules\tao\Helper\TaoHtmlHelper $vv */
$form = $vv->layuiForm();
```

### 3.1 `index.phtml`（列表）

顺序固定为：搜索区 → `#toolbar` → `#row-action`（可加 `#more-action`）→ `<table id="table" class="layui-hide">` → `$vv->layui()->addWindowConfig([])->footer()` → 渲染脚本。

```php
<fieldset class="table-search-fieldset" id="table-search">
    <legend>条件搜索</legend>
    <form class="layui-form layui-form-pane form-search" lay-filter="form-search">
        <div class="layui-form-item layui-inline">
            <?php echo $form->input('关键字', 'keyword', formItem: false) ?>
            <a class="layui-btn layui-btn-normal layui-btn-sm" lay-submit>搜索</a>
            <button type="reset" class="layui-btn layui-btn-primary layui-btn-sm">重置</button>
        </div>
    </form>
</fieldset>

<script type="text/html" id="toolbar">
    <div class="layui-table-tool-temp">
        <button class="layui-btn layui-btn-sm" lay-on="refresh"><i class="fa fa-refresh"></i></button>
        <button class="layui-btn layui-btn-normal layui-btn-sm" lay-on="create"><i class="fa fa-plus"></i>添加</button>
    </div>
</script>
<!-- 记录的其它操作通常放在 more-action 内 -->
<script type="text/html" id="more-action">
    <div class="layui-btn-container">
        <a class="layui-btn layui-btn-xs layui-btn-normal" lay-event="review">审核</a>
    </div>
</script>
<script type="text/html" id="row-action">
    <div class="layui-btn-container">
        <a class="layui-btn layui-btn-xs" lay-event="edit">编辑</a>
        <a class="layui-btn layui-btn-xs layui-btn-danger" lay-event="removeWith">删除</a>
    </div>
</script>

<table id="table" class="layui-hide"></table>
```

```js
const prefix = '<?php echo \Phax\Foundation\AppService::urlModule("yihe/car", false) ?>';
admin.table.with({url: prefix})
    .render({toolbar: '#toolbar', cols: [[
        {field: 'id', title: 'ID', width: 60},
        /* ... */
        {title: '功能', width: 80, toolbar: '#more-action'},
        {title: '操作', width: 150, toolbar: '#row-action'}
      ]]})
    .addCellEditAction()    // 单元格编辑 → POST {prefix}/modify
    .addPostSwitch()        // status 开关 → POST {prefix}/modify
    .addToolbarActions()    // refresh / create（open {prefix}/add 弹窗）
        .addRowActions({
          events: function (obj) {
            let data = obj.data;
            switch (obj.event) {
              case 'review':
                admin.iframe.open(
                        prefix + '/review?id=' + data.id, {
                          title: '课件审核',
                          end: function () {
                            admin.iframe.hasRefresh(() => { // 只有通常刷新时才需要更新数据
                              admin.table.reloadData();
                            })
                          }
                        },);
                break;
            }
          }
        });
```

- `url` 一律用 `AppService::urlModule("模块/控制器", false)` 生成，**不要手写 `/m/yihe/car`**。
- 状态列固定 `{field: 'status', title: '状态', templet: admin.table.switch}`，配合 `addPostSwitch()`；字典列用 `templet` 查模型常量（`Car::MAP_TYPE`）。
- 关联字段（`_driver_name`）由**控制器**在 `buildIndexResult()` 里补好，视图只展示（`d._driver_name || '-'`）。
- 额外能力放 `#more-action` 列，事件在 `addRowActions({events})` 的 `switch (d.event)` 中处理；打开子页统一 `admin.iframe.open(prefix + '/move?id=' + id, {title, end: () => admin.iframe.hasRefresh(() => admin.table.reloadData())})`。
- 删除前如需业务检查，调后端接口（如 `car/trip`）返回数量，再决定 `confirm` 或引导去迁移页；**真正的校验仍在后端**（`beforeDeleteQuery`）。
- 新增页面请以规范的 `yihe/site/index.phtml` 为模板。

### 3.2 `edit.phtml`（表单，增改共用）

```php
<?php
/** @var \App\Modules\tao\Helper\TaoHtmlHelper $vv */
$form = $vv->layuiForm();
?>
<form class="layui-form">
    <?php
    echo $form->input('车牌号', 'no', value: $vv->pick('no'), required: true);
    echo $form->select('车辆类型', 'type', vtOptions: \App\Modules\yihe\Models\Car::MAP_TYPE, value: $vv->pick('type'));
    echo YiheLayuiFormHelper::driverSelect($vv, value: $vv->pick('driver_id'));
    echo $form->status();
    echo $form->input('备注', 'remark', value: $vv->pick('remark'));
    echo $form->submit();
    ?>
</form>
<?php $vv->layui()->addWindowConfig()->footer(); ?>
<script>
    admin.form.submitFirst(() => { admin.iframe.close(true); });
</script>
```

- 字段值**统一走 `$vv->pick('field')`**（读 action 返回值），尽量不要写 `$_POST` / `$this->model`。
- 控件全部用 `$form`（`input` / `select` / `radio` / `textarea` / `datesPicker` / `upload` / `status` / `submit`，见 `tao/Helper/Layui/LayuiForm.php`），不要手写 `layui-form-item` 结构。
- 关联下拉复用 `Helpers/{Module}LayuiFormHelper` 的静态方法（`driverSelect` / `customerSites` / `carDriver` / `carSearchable` ...），不要在各页面重复实现搜索选择器。
- 提交统一 `admin.form.submitFirst(() => admin.iframe.close(true))`，由 `admin` 框架负责 POST 与错误提示。

### 3.3 `add.phtml`

```php
<?php
include __DIR__ . '/edit.phtml';
```

新增页与编辑页表单一致时**只写这一行**，不要复制粘贴表单。

### 3.4 其它页面

`move.phtml`、`select.phtml` 等：结构同 `edit.phtml`（`$vv` → 表单/内容 → `addWindowConfig()->footer()`），由 `index.phtml` 的 `admin.iframe.open(...)` 打开；`selectAction` 只需 `return []`。

## 4. 开发自检清单

1. 模型：强类型属性 + 默认值 + `@var` 注释；类型转换写进 `intColumns/floatColumns/bool2IntColumns/nullColumns`；字典写成 `MAP_*` 常量。
2. 控制器：`#[RBAC]` + `@property $model`；`afterInitialize()` 注入模型；只重写钩子，不重写 `index/add/edit/delete/modify`。
3. 视图：`views/layui/{controller}/{action}.phtml` 与 action 同名；`add.phtml` include `edit.phtml`；列表页只用 `admin.table` 链式 API。
4. 关联/派生字段在控制器批量补齐（`findColumn` 回填），不在视图里逐行发请求。
5. 新权限节点需在后台「系统管理 → 节点管理 → 保存分析节点」后到「角色管理」授权。
6. 开阶段 SQL 语句可保存在对应的模型文件中，由用户手动执行，或者由临时脚本完成。
7. 动过基类、钩子或接口后：先按 §2.1 的方式**实际加载类**，再跑 `vendor/bin/phpunit --testsuite {module}`（见 §6）。
8. 带前端子项目的模块（如 worksheet 的 `studio/`）：前端 dev 代理的 target **协议必须与后端一致**
   （`http://` ↔ `https://` 写错会报 `EPROTO ... tls_get_more_records:packet length too long`，与后端代码无关）；
   本地 Laragon / `php -S` 的 8071 是明文 HTTP，排障见模块 README。
9. 数据访问：服务与控制器都走模型 ORM —— 如无必要不写裸 SQL、不手写时间戳、不用 JOIN（多次查询 + PHP 组装）、
    单条查询返回模型对象；服务命名 `{ModelName}Service`、方法全静态（见 §2.2 / §5）。

## 5. 数据访问：ORM 优先（服务与控制器）

**原则：能用模型 ORM 解决的，不要写 SQL；能用基类钩子解决的，不要在控制器里重写 CRUD。**

- **服务层**（`Services/`）：数据访问一律 `Model::queryBuilder()->…->findFirstModel()/findModels()/update()` 与
  `new Model()->assign($data)->save()`；`$db->execute()/fetchOne()/fetchAll()`、`INSERT/UPDATE/SELECT` 字面量一律不用。
  单条查询**返回模型对象**（`findFirstModel()`），批量用 `findModels()`（模型数组，不用 `find()`）；需要跨表拼装时用
  「**多次 ORM 查询 + PHP 组装**」并**返回 DTO**（不要写 JOIN、不要返回裸数组）
  （范本：`worksheet/Services/WorksheetAssignmentReviewService.php` 的 `mineList()` / `stageOf()` → `worksheet/DTO/*`）。
- **控制器**：**尽量不出现 SQL** —— 增删改查交给 `BaseController` 的 CRUD + 钩子（`beforeIndexQuery` / `actionQuery` /
  `beforeModelAssign` / `beforeModelSave` / `afterModelChange` / `beforeDeleteQuery` / `afterBatchDelete`）；
  action 内需要落库时也用模型（`new Model()->assign()->save()`、`queryBuilder()->…->update()`），
  不要在控制器里手写 `$this->db->execute()/fetchOne()/fetchAll()`。
- **唯一例外**：外部库表 / 暂时没有模型的表（如按 `user_id` 查会员到期）。此时**也要补一个只读模型**
  （范本 `worksheet/Models/WorksheetVipUser.php`：只声明用到的列，用 `createdTime/updatedTime/deletedTime = ''` 关掉时间戳与软删），
  而不是把 SQL 留在控制器里。
- **时间戳与 upsert**：`created_at` / `updated_at` 由模型事件（`autoWriteTimestamp`）维护，**不要手写**；
  `ON DUPLICATE KEY UPDATE` 用「查 → 无则 `new` → `assign()` → `save()`」表达；只读/追加型表在模型里关掉对应行为。
- **⚠️ `whiteColumns` 会静默吞字段**：`Phax\Mvc\Model::assign()` 是
  `parent::assign($data, $whiteList ?: $this->whiteColumns)` —— 模型上声明了 `$whiteColumns`（后台**表单**白名单）时，
  **服务/控制器里所有没显式传白名单的 `assign()` 都会被过滤**，`uuid`/`status`/`review_version`/`latest_version`
  这类"自己算出来的字段"会被丢掉 ✗（曾表现为 `register_failed: no id`、发布快照写不进版本/状态）。
  内部写入一律用基类的 **`assignAll($data)`**（= `assign($data, array_keys($data))`），例如
  `BaseWorksheetModel::assignAll()`；后台表单路径仍用 `assign()` 受白名单约束。
- **模型 `save()` 不回填自增主键**：需要主键时用 `(int)$this->db->lastInsertId()`（或保存后 `findFirst($id)` 取回模型），
  不要直接读 `$model->id` 当新记录的主键。
- **判断标准**：只有在「模型约束确实无法满足」时才自建查询（字符串主键、外部库表等），并且要：
  1. 在类注释写明**为什么**不能复用；
  2. 尽量只替换**查询层**（自定义 `Model` 或覆写 `buildIndexResult`），保留
     `beforeModelAssign / beforeModelSave / afterModelChange / beforeDeleteQuery` 钩子语义；
  3. 不要新增与基类同义的 action（`status` / `modify` / `batchChange` 之类）。

## 6. 接口测试（PHPUnit）

模块的接口回归统一写成 PHPUnit，**不要再写 shell + curl 冒烟脚本**（Windows/CI 不可移植、断言弱、
没有 IDE 跳转；`worksheet/tests/acceptance.sh` 已按本节迁到 `tests/PHPUnit/Controllers/`）。

### 6.1 位置与运行

| 项 | 约定 |
|---|---|
| 目录 | `App/Modules/{module}/tests/PHPUnit/**/{Xxx}Test.php`（namespace 与目录一致，PSR-4 `App\` → `App/`） |
| 助手 | `App\Modules\tao\tests\Helper\MyTestTaoHttpHelper`（继承 `Tests\Helper\MyTestHttpHelper`） |
| 测试目标 | **真实运行的后台**：`TEST_ORIGIN`（`src/tests/bootstrap.php`，默认 `http://localhost:8071`），不是进程内 mock |
| 注册 testsuite | 加在**本地 `src/phpunit.xml`**（该文件已被 .gitignore，各人自持；`phpunit.example.xml` 只作模板，不加模块条目）：`<testsuite name="{module}"><directory>App/Modules/{module}/tests/PHPUnit</directory></testsuite>`（参照已有的 `test-module` / `worksheet` / 注释掉的 `yihe`） |
| 运行 | `cd src` → `cp phpunit.example.xml phpunit.xml`（首次；`phpunit.xml` 已被 .gitignore）→ **`php artisan test --testsuite worksheet`**（等价 `vendor/bin/phpunit --testsuite worksheet`；`artisan test` 走 `src/routes/cli.php` 的 `test` 命令转发参数） |
| 只看用例（不跑） | `vendor/bin/phpunit --bootstrap tests/bootstrap.php --no-configuration --list-tests App/Modules/{module}/tests/PHPUnit` —— 不需要后台在跑，用来验证命名空间 / 自动加载 / `#[Depends]` 链 |
| 前置 | 后台已启动；`app.test.open = true`（`config.php`），否则 `->login()` 不生效；**表结构迁移已执行**（见模块文档的「迁移」，如 `worksheet/docs/assignment-lifecycle.md` §9），否则会出现 1267 / 1366 那类错 |
| 副作用 | 会写真实数据（DB + `upload/`）；清理 SQL 见模块 README |

### 6.2 请求与登录

- **登录**：`->login()` 发送 `test-token` 请求头，命中 `LoginDemoTokenAuthAdapter`
  （`AppService::isTest() && hasHeader('test-token')`）；token → user id 见 `config.php` 的 `app.test.tokens`
  （默认 `tao` = 1）。`MyTestCurl::pathTest()` 会给每个请求自动追加 `?test=on`（`isTest()` 的条件），**不要手写**。
- 写接口一律 `->login()`（只认同源后台会话）；读接口保持匿名，正好用来断言 `401`。
- 构造顺序：`get/post/put(...)` → `setJsonBody([...])` / `login()` → `send()`。
  **`setJsonBody()` 必须写在 `post()/put()` 之后** —— `request()` 会重置整份 curl 配置，写在前面会被冲掉。
- 传二进制（模块的 `application/zip` 接口）：测试助手没有 raw body 能力，用模块自带的 JSON 兼容路径
  `setJsonBody(['packageBase64' => base64_encode($zip)])`（见 `WorksheetBaseController::packageBytes()`），
  不要去改公共测试基建。

### 6.3 断言速查（都是踩过的坑）

| 写法 | 说明 |
|---|---|
| `->send()->notContainsFailed()` | 排除 PHP 警告 / fatal / `Call Stack`；**错误响应用例不要调它**（`?test=on` + debug 下 `error()` 的 `data` 是 backtrace，会误报） |
| `->testResponseCode0()` | 断言 `code=0`，返回整个响应；数据在 `$response['data']` |
| `->jsonResponseData()` | 断言 `msg` **为空**并直接返回 data；带提示语的接口（"已提交审核"）不能用 |
| `->jsonResponse()['code']` | 断言错误码（`401 / 403 / 404 / 409`） |
| `->assertContent($raw)` / `$http->content` | 二进制响应（zip / PNG）逐字节比对 |
| `->assertHttpCode(200)` | 静态/上传文件（`/mupload/worksheet/...`）可访问性 |
| `#[Depends('testXxx')]` | 串联流程并**传递返回值**（register 的 uuid → 保存 → 发布 → 下载 → 提交）。**被依赖的用例必须 `return $ctx;`**（声明成 `: array`），否则 PHPUnit 传 `null`，下游报 `Argument #1 ($ctx) must be of type array, null given`；返回类型写 `void` 最容易被漏掉 |

### 6.4 最小模板

```php
namespace App\Modules\worksheet\tests\PHPUnit\Controllers;

use App\Modules\tao\tests\Helper\MyTestTaoHttpHelper;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class XxxApiTest extends TestCase
{
    private const PREFIX = '/api/m/worksheet';

    public function testRegister(): array
    {
        $response = (new MyTestTaoHttpHelper($this))
            ->post(self::PREFIX . '/assignment/register')
            ->setJsonBody(['title' => 'phpunit-' . date('YmdHis')]) // 必须在 post() 之后
            ->login()->send()->notContainsFailed()->testResponseCode0();
        $this->assertNotEmpty($response['data']['uuid']);
        return $response['data'];                                   // 传给下一个用例
    }

    #[Depends('testRegister')]
    public function testWriteWithoutLoginRejected(array $ctx): void
    {
        $response = (new MyTestTaoHttpHelper($this))
            ->post(self::PREFIX . '/project/save/' . $ctx['uuid'])
            ->setJsonBody(['title' => 'x', 'revision' => 0])
            ->send()->jsonResponse();   // 故意不 login()，且不要 notContainsFailed()
        $this->assertSame(401, $response['code']);
    }
}
```

覆盖建议：正常主流程 + 权限边界（未登录 401 / 非作者 403）+ 乐观锁 409 + 二进制内容一致性。
参考实现：`worksheet/tests/PHPUnit/Controllers/{ApiSmokeTest,AssignmentLifecycleTest}.php`。

### 6.5 跑红了先看这里（实战顺序）

先分清"测试写错"还是"业务 / 表结构错"，**不要急着改断言**：

| 症状 | 通常是 |
|---|---|
| `... Argument #1 ($ctx) must be of type array, null given` | `#[Depends]` 上游用例没 `return` 上下文（见 6.3） |
| 接口 `code=0` 但列表**恒为空** | 后端把 SQL 异常 `catch` 成空了。模块约定：**影响"对外可见性 / 数据正确性"的查询与写入不许 `try/catch{ return 空 }`**——要么别 catch，要么 `error_log` 后再抛。静默空值会把排查方向带偏（worksheet 曾因此白查一轮） |
| `1267 Illegal mix of collations (...utf8mb4_0900_ai_ci) and (...utf8mb4_unicode_ci) for operation '='` | 新表的 collation 与既有表不一致（**MySQL 8 建表默认 `utf8mb4_0900_ai_ci`**）。建表显式写 `DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`；存量表 `CONVERT TO ...`；跨表 join 可写 `column = a.uuid COLLATE utf8mb4_unicode_ci` 兼容两种规则（保留左列索引） |
| `1366 Incorrect integer value: 'asg-...' for column 'assignment_id'` | 该列还停在旧 DDL（`int`），而对齐后的设计是存**对外 uuid**。判断标准：所有引用 `uuid` 的列都应是 `varchar(64) utf8mb4_unicode_ci` |
| 写接口"返回 `code=0`，紧接着下游 404 / 查不到" | 后续写入被 `try/catch{ // 忽略 }` 吞了（如发布快照）。**快照 / 状态类写入必须让调用方看到失败** |

排障三件套（都不用改测试）：

```bash
cd src
# 1) 直接打接口看真实响应（测试就是这么发的：?test=on + test-token）
curl.exe -sS "http://localhost:8071/api/m/worksheet/user.assignment/index?uid=1&test=on"
curl.exe -sS -X POST "http://localhost:8071/api/m/worksheet/assignment/register?test=on" -H "test-token: tao"
# 2) 只列用例，先排除"发现/加载"问题
vendor/bin/phpunit --bootstrap tests/bootstrap.php --no-configuration --list-tests App/Modules/worksheet/tests/PHPUnit
```

```php
<?php // 3) 核对表结构（类型 / collation 一眼看出）；放 src/ 下临时跑，用完删掉
require __DIR__ . '/tests/bootstrap.php';
$db = \Phax\Foundation\Application::di()->getShared('db');
print_r($db->fetchAll(
    "SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, COLLATION_NAME FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME IN ('uuid','assignment_id')
      ORDER BY TABLE_NAME",
    \Phalcon\Db\Enum::FETCH_ASSOC
));
```
