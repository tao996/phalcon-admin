<?php

/**
 * ┌─────────────────────────────────────────────────────────────┐
 * │              Migration 全局配置（瘦身版）说明                 │
 * └─────────────────────────────────────────────────────────────┘
 *
 * 结构迁移的 scope 已下放到各模块/项目自治，本文件只保留全局项。
 * 拷贝为 src/config/migration.php 后按需修改。
 *
 * ════════════════ Scope 自动发现 ════════════════
 *
 * artisan migration 会自动发现以下配置（文件存在即参与迁移）：
 *
 *   App/Modules/<名称>/config/migration.php   → scope "module:<名称>"
 *   App/Projects/<名称>/config/migration.php  → scope "project:<名称>"
 *
 * 模块配置内容（directory 按约定推导为 App/<类型>/<名称>/data/migration）：
 *
 *   return [
 *       'table_prefix' => 'tao_',          // 该模块管理的表前缀（g 时自动匹配）
 *       'export' => [                      // 可选：随迁移导出/还原数据的表
 *           'tao_system_menu' => 'always',
 *       ],
 *   ];
 *
 * 没有该文件 = 该模块/项目不参与迁移。
 *
 * ════════════════ 基础数据（seed） ════════════════
 *
 * 跨项目共享的基础数据放在模块/项目的 data/seed/*.sql（幂等写法：
 * REPLACE INTO / ON DUPLICATE KEY UPDATE），执行：
 *
 *   php artisan db:seed              # 模块/项目 seed + 记账（seed_history 表）
 *   php artisan db:seed --dir=xxx    # 追加外部目录（如 deploy 上传的项目差异数据）
 *
 * ════════════════ 全局配置项 ════════════════
 *
 *   database   数据库连接（可选，不填用应用默认）
 *   ts_based   时间戳版本号（true/false）
 *   order      执行顺序声明（scope 键或模块名），tao 为基础模块务必排最前；
 *              未列出的 scope 按 scope 键排序追加
 *   scopes     （向后兼容）旧式集中注册，优先级高于自动发现，不再推荐
 */

return [
    'database' => [
        'adapter' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'dbname' => 'phalcon-admin-clean',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'options' => [
            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'",
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ],
    ],

    'ts_based' => true,

    // 执行顺序：tao 为基础模块务必排最前；未列出的自动发现 scope 按 scope 键排序
    'order' => [
        'module:tao',
    ],
];
