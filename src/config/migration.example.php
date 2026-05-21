<?php

/**
 * if you don't need this file, the migration will read db setting from config.php file
 *
 * how to use this file, here is an example
 * 1. copy this file to `migration.php` in the same direction, and modify the database setting
 * 2. export tables and it's data
 *    `php artisan g --table=demo_ --datas`
 *    now you will find the data save in storage/data/migration/1.0.0
 * 3. import tables and it's data
 *    `php artisan r --config=config/migration.php`
 * 4. check the result
 */
return [
    'g' => [],
    'r' => [],
    'database' => [
        'adapter' => 'mysql',
        'host' => 'host.docker.internal',
        'port' => 3306,
        'dbname' => 'docker_phalcon_github', // a database with base structure and clean data
        'username' => 'demo',
        'password' => '123456',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'options' => [
            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'",
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        ]
    ]
];