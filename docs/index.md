# 简介

Phalcon Admin 基于 Phalcon(5.8) 框架，用于快速开发 Web 站点；

> 这是一个为创业公司准备的工具，用于快速地创建应用测试市场。


### 主要特性

* 支持多应用

```
当你需要为多位客户提供定制化站点，而这些站点都拥有大部分相同的代码（框架/后台）
每个应用可支持子目录功能，每个应用支持绑定多个不同的域名
每个应用允许有自己的配置文件

src/App/Projects/
    |-- demo        # a demo to show how the project work， use `/p/demo` to visit it
```

* 支持多模块

```
when: 集成更多功能，并可将模块打包给他人使用；每个模块都是独立的，意味着你可以安全地移除它（除非你在其它的模块或应用中引用它）
每个模块还支持子模块，子目录功能

src/App/Modules
    |-- demo    # a demo module to show how the Modules work, use `/m/demo` to visit it
```

* 动态路由

```
根据路由来加载不同的控制器，返回不同模式的数据

[语言/][api/][p/ProjectName/|m/ModuleName/][controller/][action/][params]
```

### 目录结构

如果你用过 `laravel`, 那这个目录结构会比较熟悉

```
phalcon-admin/
|-- backup/                             # a dir that for backup/update setting files for `php admin` toolkit
|-- docker/                             # anything about docker
    |-- log/
        |-- mysql/                      # mysql service log dir
        |-- nginx/                      # nginx service log dir
        |-- php/                        # php service log dir
    |-- mysql/
        |-- sql/
            |-- 001-create-tables.sql   # init db data, 如果使用 mysql 服务，则会自动加载；否则请自行导入到数据库中
        |-- my.example.cnf              # mysql 服务配置
    |-- nginx/
        |-- sites/
            |-- default.example.conf    # site config for nginx service
            |-- assets.example.conf     # be include for default.example.conf, make your assets independent to your project or module
        |-- host.example.conf           # you can copy it to /etc/nginx/conf.d if you use nginx proxy
        |-- host.location.example.txt   # proxy setting for host.example.conf, copy to nginx conf.d together
    |-- php/
        |-- php.example.ini             # php service image use this file like php.ini
    |-- storage/
        |-- mysql/                      # mysql image data, (zip this dir to replace the backup/ dir ?)
        |-- redis/                      # redis image data
|-- src/                            # the main source code
    |-- App                         
        |-- Console                 # the default cli tasks, see the src/routes/cli.php for details
        |-- Http                    # the default web home page
        |-- Modules/
            |-- demo                # a demo how to use module, http://localhost:8071/m/demo
        |-- Projects/
            |-- demo                # a demo how to use project, http://localhost:8071/p/demo
    |-- bootstrap/
        |-- app.php                 # bootstrap file
    |-- config/
        |-- config.php              # you phalcon services global config file
        |-- project.php             # project config, for demo/test, domain mapping
    |-- public/                     # warning: save you js/css/image in your project/module, don't put them in the public dir
        |-- index.php               # entry file
    |-- routes/
        |-- cli.php                 # command route, for unitest, migration
        |-- web.php                 # nothing here, most of time you don't need it
    |-- storage/                    # tmp dir when running
        |-- app/                    # some cli pid file are save here
        |-- cache/                  # session/view save here
        |-- data/
            |- migration            # phalcon migrate tool read/save db migrate data here
        |-- logs/                   # exception or sql log save here
    |-- tao996/                     # a library than make this project work
        |-- phar/                   
            |-- dotenv.phar             # https://github.com/symfony/dotenv
            |-- phalcon-migrations.phar # https://github.com/phalcon/migrations , modify to make it work together with phalcon-admin
            |-- phpseclib.phar          # `php admin` tool, use this to backup/upload files from/to production server
        |-- Phax
            |-- Db/                 # db classes, make ORM write like laravel/thinkphp
            |-- Events/             # I use it in development mode
            |-- Foundation/         # load service and route
                |-- Application.php # load env/config/service....
                |-- Route.php       # save the current request info
            |-- Helper/             # some classes about phalcon
            |-- Mvc/                # base Controller/Model/Module
            |-- Support/
                |-- Facade/         # some function classes for phalcon
                |-- I18n/           # language
                |-- Validation/     # extend phalcon validation
            |-- Router.php          # analysis request url
            |-- ...

|-- .env.example                    # docker-compose.yaml setting
|-- docker-compose.example.yaml     
|-- toolkit/                        # files for admin
|-- admin                           # a tool to help manage phalcon-admin project, `php admin` to see the help
```
