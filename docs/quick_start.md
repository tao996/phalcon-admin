`phalcon-admin` 提供了两个克隆地址

* `https://github.com/tao996/phalcon-admin.git`
* `https://gitee.com/taoooo/phalcon-admin.git` CN

注意： 所有包含 `.example` 的文件都是为快速开发准备的

```
# git clone --depth=1 https://gitee.com/taoooo/phalcon-admin.git 
git clone --depth=1 https://github.com/tao996/phalcon-admin.git

cd phalcon-admin

php admin quick
# 注意，如果你不想直接执行 `php admin quick`, 则可以通过下面的命令达到相同的效果
# cp .env.example .env
# cp docker-compose.example.yaml docker-compose.yaml
# cp src/config/config.example.php src/config/config.php

# 注意，如果你是 Linux 系统, 还需要执行 `chmod -R 777 str/storage` 以确认 docker 能写入日志
docker compose up -d # or docker-compose up -d
```

docker 运行后，可以访问 http://localhost:8071 `(php-fpm)`, 或者 http://localhost:8072 `(workerman 模型)`, 


* 如果需要更好的控制全部的配置文件，请查看 [admin](admin.md) 一节；
* 如果你想要构建自己的 phalcon image，请访问 https://github.com/tao996/phalcon-docker-images

### PHPStorm

使用 `PHPStorm` 编写代码时，将下面这些文件夹排除（right click => Make Directory as > Excluded）

* backup
* docker
* src/storage

## windows

* [phalcon 8.3](https://pecl.php.net/package/phalcon)
* [redis](https://pecl.php.net/package/redis)

添加到 `php.ini` 后需要重新 `nginx`