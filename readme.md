## 警告 Warning

This is just a develop DEMO, don't use it in production.

DEMO，请勿使用在生产环境中，后果自负。

## 准备

* 必须 `php`, `nginx`, `docker`, `docker-compose`
* 可选 `mysql`, `redis`

### 问题

* docker 对 `src/storage` 目录没有写权限，导致写入日志失败

```
# 方法一：使用指定的用户启动 docker

adduser dockeruser
systemctl stop docker
systemctl start docker --user=dockeruser

chown -R dockeruser:dockeruser /path/to/src/storage
chmod -R 775 /path/to/src/storage

# 方法二：直接修改目录权限

chmod 777 /path/to/src/storage
```