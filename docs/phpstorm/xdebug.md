## Xdebug 本机配置
```
[xdebug]
zend_extension = "D:/apps/laragon/bin/php/php-8.3.11-Win32-vs16-x64/ext/php_xdebug.dll"
xdebug.mode = debug
xdebug.client_host = 127.0.0.1

; 1. 修改为推荐的空闲端口
xdebug.client_port = 51712

; 2. 强烈建议改为 trigger（按需触发）
; 这样只有当你开启浏览器插件或传入?XDEBUG_SESSION 时才激活调试，
; 不会每个普通请求都去连接调试器，性能会好很多
xdebug.start_with_request = trigger
xdebug.idekey = xdebug
; 调试日志（仅在排查连接问题时开启，平时注释掉）
;xdebug.log = "D:\apps\laragon\tmp\xdebug\xdebug.log"
;xdebug.log_level = 0
```


## Xdebug in Docker

注意：如果是运行在 `docker` 环境下，请确保已经按照 [docker 环境](docker.md) 设置好配置。

默认配置已经在 `php/php.example.ini` 中存在，不建议直接修改 `php.example.ini` 文件，可以复制一份修改名称为 `php.ini`（注意修改 `docker-compose.yaml` 中 php service 对应的配置）

```ini
[xdebug]
; 在 authus/phalcon:5.13.0 中不需要开启
;zend_extension=xdebug.so
xdebug.mode=debug,develop
xdebug.discover_client_host=0
xdebug.idekey=docker
xdebug.client_port = 19003
xdebug.client_host=host.docker.internal
; 容器内直连宿主机 PhpStorm，无需 DBGp Proxy
xdebug.start_with_request=trigger
xdebug.log_level = 0
```

如果你本地机器已经安装了 `xdebug` 那么就不能继续使用 `9003` 端口了

修改 `docker-compose.yaml` 中 `php` 服务的配置（可参考 `docker-compose.example.yaml` 文件）

记住以下信息

* `xdebug.client_port` 修改为 `19003`
* `xdebug.idekey` 修改为 `docker` 
* `serverName` 修改为 `phalcon-admin`,

下面的 `PHPStorm Setting` 将使用到这些信息

### PHPStorm Setting

* PHP > Servers

创建一个新的服务，名称为 `phalcon-admin`（必须与上面的 `serverName` 属性值保持一致），配置如下

```
Host    : localhost
Port    : 8071
Debugger: Xdebug

[checked]Use path mappings...

File/Director       Absolute path on the server
...
    >src             /var/www
```

![PHP Servers Example](../assets/images/php.Servers.jpg)

* PHP > Debug

将我们 `xdebug.client_port` 设置的 `19003` 添加到 `Debug port` 中.

![PHP Debug](../assets/images/php.debug.jpg)

* Run/Debug Configuration

添加运行配置，类型为 `PHP Web Page`, 服务选择 `phalcon-admin`，我们将其命名为 `index8071`

![Configuration](../assets/images/xdebug.configuration.jpg)

### Test Result

1. 选择刚刚创建的 `index8071`
2. 打开连接监听开关
3. 在待访问页面打上断点（示例为 `src/App/Http/Controllers/IndexController.php`，即默认首页）
4. 在浏览器中访问默认的首页 http://localhost:8071

![Test Result](../assets/images/debug.ok.jpg)

如果配置成功，那么我们就可以在 IDE Debug 区域看到调试结果


### 常见问题


* `9003` is busy，`Server name` 显示为空，`port` 总是 80

移除 xdebug 配置中的 9003，可能被本地 php 环境占用了

* 点击 debug 后自动打开链接 `http://localhost:8071` 但是断点没有中断

IDE 配置 `PHP > Servers` 中的 `Name`与 `docker-compose.yaml` 中 php 服务的 `serverName` 不一致

* 在浏览器中 debug 正常，在其它 api 工具之类的不正常，总会自动跳过断点；

原因暂未知

1. 不知道具体原因，直接删除项目下的 `.idea` 目录，然后重新打开，再配置
2. 更换 image, 使用其它版本的 xdebug
3. 更换 PHPStorm