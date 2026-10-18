# 桌面模板 → 移动模板 生成指南

根据 `index.phtml`（桌面版/Layui table）生成 `index_mobile.phtml`（移动版/卡片流）时，**参数处理逻辑必须保持一致**。

---

## 核心原则

> 移动版的请求参数处理方式，必须与桌面版完全一致。

| 场景 | 桌面版 `admin.table` | 移动版 `admin.mobilePage` |
|------|---------------------|--------------------------|
| **首次加载** | 只发 `prefix` URL 参数，**不含表单数据** | 必须同样只发 URL 参数 |
| **搜索后** | `table.reloadData({where: form.val()})` 发送表单数据 | 保存表单数据，后续请求携带 |
| **滚动/翻页** | 复用当前 `where` 参数 | 复用最近一次搜索时的表单数据 |
| **重置** | 回到初始状态 | 回到仅 URL 参数状态 |

---

## 桌面版 `index.phtml` 参数机制（必须了解）

### URL 携带链接参数

```php
<?php
$driverId = \Phax\Utils\MyData::getInt($_GET, 'driver_id');
$customerId = \Phax\Utils\MyData::getInt($_GET, 'customer_id');
$month = \Phax\Utils\MyData::getString($_GET, 'month');
?>
```

### 构造 prefix（包含 URL 参数）

```php
var PREFIX = '<?php echo $vv->urlModule("yihe/trip", [
        'driver_id' => $driverId, 'month' => $month, 'customer_id' => $customerId,
]) ?>';
```

### 表单搜索：仅在搜索时发 form data

`admin.table.with({url: PREFIX}).render(...)` 内部处理表单搜索：

```javascript
// 搜索时，收集表单数据作为 where，替换 URL 自带参数
submitElem.bind('click', function (e) {
    const data = form.val('form-search');
    tableInst.reloadData({
        where: data,    // 替换 where，URL 原有参数被覆盖
        page: {curr: 1}
    });
});
```

**关键点**：首次渲染时，Layui table **只发送 `prefix` 上的 URL 参数**，没有表单 `where` 数据。表单数据仅在点击「搜索」后发送。

---

## 移动版 `index_mobile.phtml` 实现规则

### 方法：`_searchData` 状态标志

使用一个状态变量区分"首次加载"和"搜索后"：

```javascript
/** @type {null|Object} null=未搜索（只带 URL 参数），Object=搜索后的表单数据 */
var _searchData = null;

// getSearchData：首次加载返回 {}（只保留 URL 参数），搜索后返回表单数据
function getSearchData() {
    if (_searchData === null) {
        return {};
    }
    return _searchData;
}

// 搜索时保存表单数据（在 admin.mobilePage.init() 之前绑定）
layui.use('form', function () {
    var form = layui.form;
    form.on('submit', function () {
        var data = layui.form.val('form-search');
        // 补充日期等表单中没有 name 的字段
        data.dt_start = $('input[name="dt_start"]').val() || '';
        data.dt_end = $('input[name="dt_end"]').val() || '';
        _searchData = data;
        // admin.mobilePage.init 内部已绑定 submit 处理程序，会自动调 load
    });
    // 重置后恢复初始状态（只带 URL 参数）
    $('button[type="reset"]').on('click', function () {
        _searchData = null;
    });
});

admin.mobilePage.init({
    url: PREFIX,
    getSearchData: getSearchData,
    renderCards: renderCards,
    onSummary: function (s) { /*...*/ }
});
```

### 不要做的事情

1. **不要在 `getSearchData()` 里写 PHP 初始值兜底**——那跟桌面版逻辑不一致，桌面版也是用表单数据覆盖 URL 参数的。

2. **不要重复读取 `#cs_car_id` / `#cs_driver_id` 等隐藏域**——这些字段已有 `name="car_id"` / `name="driver_id"`，`form.val('form-search')` 已自动捕获它们。

3. **不要把 `getSearchData()` 写成直接返回 `form.val()`**——那样首次加载也会发送表单数据，覆盖 URL 参数。

---

## 完整示例对照

### 桌面版 `index.phtml` 片段

```php
<?php
$driverId = \Phax\Utils\MyData::getInt($_GET, 'driver_id');
$customerId = \Phax\Utils\MyData::getInt($_GET, 'customer_id');
?>
<!-- 表单：含 name="driver_id" 的隐藏域（由 driverSearchable 生成） -->
<form class="layui-form layui-form-pane form-search" lay-filter="form-search">
    <?= YiheLayuiFormHelper::driverSearchable($vv) ?>
</form>

<script>
const prefix = '<?php echo $vv->urlModule("yihe/trip", [
        'driver_id' => $driverId, 'customer_id' => $customerId,
]) ?>';
admin.table.with({url: prefix}).render({...});
</script>
```

### 对应的移动版 `index_mobile.phtml` 片段

```php
<?php
$driverId = \Phax\Utils\MyData::getInt($_GET, 'driver_id');
$customerId = \Phax\Utils\MyData::getInt($_GET, 'customer_id');
?>
<!-- 表单与桌面版完全一致 -->
<form class="layui-form layui-form-pane form-search" lay-filter="form-search">
    <?= YiheLayuiFormHelper::driverSearchable($vv) ?>
</form>

<script>
var PREFIX = '<?php echo $vv->urlModule("yihe/trip", [
        'driver_id' => $driverId, 'customer_id' => $customerId,
]) ?>';

/** @type {null|Object} null=未搜索（只带 URL 参数），Object=搜索后的表单数据 */
var _searchData = null;

function getSearchData() {
    if (_searchData === null) return {};
    return _searchData;
}

// 搜索保存表单数据，重置恢复初始（在 init 之前绑定）
layui.use('form', function () {
    var form = layui.form;
    form.on('submit', function () {
        _searchData = layui.form.val('form-search');
    });
    $('button[type="reset"]').on('click', function () {
        _searchData = null;
    });
});

admin.mobilePage.init({
    url: PREFIX,
    getSearchData: getSearchData,
    renderCards: renderCards,
});
</script>
```

---

## 校验清单

生成/修改移动模板后，检查以下各项：

- [ ] 首次加载 URL 参数（如 `?driver_id=123`）是否生效
- [ ] 搜索后表单数据是否正确发送
- [ ] 滚动加载是否携带相同的搜索参数
- [ ] 重置后是否回到初始状态（仅 URL 参数）
- [ ] `getSearchData()` 中不包含 `0` / `''` 等空值覆盖 URL 参数
- [ ] 无重复读取 `#cs_car_id` / `#cs_driver_id` 等隐藏域
