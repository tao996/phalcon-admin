## 路由

对于 `$_SERVER['REQUEST_URI']` 请求, 我们会使用 `src/tao996/Phax/Foundation/Context/RouteMatchContext.php` 对其进行分析；

更多细节请查看 `src/tao996/Phax/Foundation/Application.php` 中的 `routeWith` 方法；

### 请求地址格式

匹配模式 `[语言/][api/][p/ProjectName/|m/ModuleName/][controller/][action/][params]`

* `[语言]` 可选，支持 `zh-CN` 或 `cn`，正则表达式 `'/{language:[a-zA-Z]{2}(-[a-zA-Z]{2})?}'`
* `[api]` 可选
* `[p/ProjectName]` 多应用请求，如 `p/demo`
* `[m/ModuleName]` 多模块请求，如 `m/demo`
* `controller` 控制器，默认为 `index`
* `action` 操作，默认为 `index`
* `params` 参数，作为 action 的参数传递，如 `indexAction(string $name, int $age)` ,`/index/index/John/15` 时 `$name='John', $age=15`

## 默认 App

非应用和模块请求的地址，即不包含 `/[p|m]/xxx`

### `/控制器`


* 命名空间 `App\Http\Controllers`, 
* 视图目录 `PATH_APP. "Http".DIRECTORY_SEPARATOR."views"`

| 地址              | controller | action  | params |
|-----------------|------------|---------|----------| 
| `/` 或 `空`       | `index` | `index` |          |
| `/c1`           | `c1` | `index` |          |
| `/c2/a2`        | `c2` | `a2`    |          |
| `/c2/a2/p1`     | `c2` | `a2`    | `p1`     |
| `/c2/a2/p1/...` | `c2` | `a2`    | `p1/...` |


### `/子目录.控制器`

* 命名空间 `App\Http\Controllers\子目录`
* 视图目录 `PATH_APP. "Http".DIRECTORY_SEPARATOR."views/子目录"`

| 地址              | controller | action  | params |
|-----------------|------------|---------|--------|
| `/子目录.c1`       | `c1` | `index` |        |
| `/子目录.c1/a1`    | `c1` | `a1`    |        |
| `/子目录.c1/a1/p1` | `c1` | `a1`    | `p1`    |

## 多应用

多应用保存位置 `src/App/Projects`

* 请求路径 `p/xxx[/controller[/action[/params]]]`

## 多模块

### `/m/模块`

基本模块请求路径 `/m/模块名称[/controller[/action[/params]]]`，默认模块名称为 `index`

* 加载模块 `PATH_APP_MODULES.'模块名称'.DIRECTORY_SEPARATOR.'Module.php'`
* 命名空间 `App\Modules\模块名称\Controllers`
* 视图目录 `PATH_APP_MODULES. '模块名称'.DIRECTORY_SEPARATOR.'views'`

| 地址               | module   | controller | action  | params |
|------------------|----------|------------|---------|--------| 
| `/m/`            | `index` | `index`    | `index` |        |
| `/m/m1`          | `m1` | `index`    | `index` |        |
| `/m/m1/c1`       | `m1` | `c1`       | `index` |        |
| `/m/m1/c1/a1`    | `m1` | `c1`       | `a1`    |        |
| `/m/m1/c1/a1/p1` | `m1` | `c1`       | `a1`    | `p1`    |

### `/m/模块.子模块` 

子模块请求路径 `/m/模块名称.子模块[/controller[/action[/params]]]`

* 加载模块 `PATH_APP_MODULES.'模块名称/Module.php'` 同基本模块
* 命名空间 `App\Modules\模块名称\A0\子模块名称\Controllers`
* 视图目录 `PATH_APP_MODULES. '模块名称/A0/子模块名称/views'`

| 地址              | module | subModule | controller | action  | params |
|-----------------|-----|-----------|------------|---------|--------|
| `/m/tao.wechat` | `tao` | `wechat`  | `index`    | `index` | |
| `/m/m1.m11/c1`  | `tao` | `m11`     | `c1`       | `index` | |

### `/m/模块/子目录.控制器`

多模块子目录请求地址 `/m/模块/子目录.控制器`

* 加载模块 `PATH_APP_MODULES.'模块名称/Module.php'` 同基本模块
* 命名空间 `App\Modules\模块名称\Controllers\子目录名称`
* 视图目录 `PATH_APP_MODULES. '模块名称/views/子目录'`

### `/m/模块.子模块/子目录.控制器`

`子模块+子目录` 请求地址 `/m/模块.子模块/子目录.controller[/action[/params]]`

* 加载模块 `PATH_APP_MODULES.'模块名称/Module.php'` 同基本模块
* 命名空间 `App\Modules\模块名称\A0\子模块名称\Controllers\子目录名称`
* 视图目录 `PATH_APP_MODULES. '模块名称/A0/子模块名称/views/子目录'`