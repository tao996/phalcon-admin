# Phalcon Admin Deploy UI

桌面部署工具，替代 `php deploy` 命令行操作。

## 使用方式

### 1. 生成配置文件

```bash
cd phalcon-admin

# 导出服务器+项目合并配置为 JSON
php deploy config:export yihe --save=deploys/.cache/config.json
```

### 2. 安装依赖

```bash
pip install -r deploysUI/requirements.txt
```

### 3. 启动 UI

```bash
python deploysUI/main.py
```

启动后：**文件 → 加载配置** → 选择刚才生成的 `deploys/.cache/config.json`

### 菜单说明

```
文件 → 加载配置      选择 JSON 配置文件
文件 → 退出          关闭程序
SSH  → 连接          连接远程服务器
SSH  → 断开          断开连接
```

### 标签页

| 标签 | 功能 |
|---|---|
| 服务器 | 环境检测、Nginx 重载/日志 |
| 项目 | 部署、更新、推送配置、重置代码 |
| Docker | 容器状态、重启、日志 |
| Nginx | 添加/移除域名、SSL 证书 |
| 数据库 | SSH 隧道、phpMyAdmin |

## 打包为独立程序

```bash
pip install pyinstaller
pyinstaller --onefile --windowed deploysUI/main.py --name deployUI
```

Windows: `dist/deployUI.exe`  
macOS: `dist/deployUI.app`  
Linux: `dist/deployUI`

> 注意：需要先安装 Python 3.9+，打包后可脱离 Python 环境运行。

## 目录结构

```
deploysUI/
├── main.py              ← 入口
├── requirements.txt     ← 依赖
├── README.md            ← 本文件
├── core/                ← 后端逻辑
│   ├── ssh.py           — SSH 连接
│   ├── config.py        — 配置加载
│   ├── template.py      — 模板渲染
│   ├── docker.py        — Docker 操作
│   ├── git.py           — Git 操作
│   ├── router.py        — Nginx 管理
│   └── deployer.py      — 部署编排
└── panels/              ← UI 面板
    ├── base.py          — 基类
    ├── server_panel.py  — 服务器
    ├── project_panel.py — 项目
    ├── docker_panel.py  — Docker
    ├── nginx_panel.py   — Nginx
    └── db_panel.py      — 数据库
```
