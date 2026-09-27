<?php

/**
 * tao 模块迁移配置（由 artisan migration 自动发现）
 *
 * directory 按约定推导为 App/Modules/tao/data/migration，无需配置
 */
return [
    'table_prefix' => 'tao_',
    // 以下表的初始数据随迁移一起导出和还原
    'export' => [
        'tao_cms_page' => 'always',
        // 存在关联关系
        'tao_cms_content' => 'always',


        'tao_open_config' => 'always',
        'tao_system_config' => 'always',
        'tao_system_menu' => 'always',
        'tao_system_node' => 'always',
        'tao_system_user' => 'always',
    ],
];
