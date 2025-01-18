<?php

/*
* Copyright (c) 2024-present
* Author: tao996<lvshutao@outlook.com>
* 
* For the full copyright and license information, please view the LICENSE.txt
* file that was distributed with this source code.
*/
if (explode('.', phpversion())[0] < 8) {
    die('最低版本 php 8');
}
define('PATH_ROOT', str_replace('\\', '/', dirname(__DIR__) . '/'));
const PATH_SRC = PATH_ROOT . 'src/';
const PATH_ADMIN_BACKUP = PATH_ROOT . 'backup/';
const PATH_ADMIN_TMP_FILE = PATH_ADMIN_BACKUP . 'tmp.php';

include_once PATH_SRC . 'tao996/phar/dotenv.phar';
spl_autoload_register(function ($class) {
    if (str_starts_with($class, 'Phax\\')) {
        include_once PATH_SRC . 'tao996/' . str_replace('\\', '/', $class) . '.php';
    }
});
const IS_TASK = true;
const IS_WORKER_WEB = false;
require_once PATH_SRC . 'tao996/Phax/function.php';
require_once __DIR__ . '/Artisan.php';
require_once __DIR__ . '/const.php';
require_once __DIR__ . '/G.php';
require_once __DIR__ . '/LocalProject.php';
require_once __DIR__ . '/SyncProject.php';
require_once __DIR__ . '/SshConfig.php';
require_once __DIR__ . '/RemoteCmdManager.php';
