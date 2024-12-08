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
require_once __DIR__ . '/const.php';
require_once __DIR__ . '/G.php';
require_once __DIR__ . '/LocalProject.php';
require_once __DIR__ . '/SyncProject.php';
require_once __DIR__ . '/SyncConfig.php';
require_once __DIR__ . '/SyncServer.php';

$g = new G();
switch ($g->action) {
    case 'quick':
        $local = new LocalProject($g);
        $local->runQuickStart();
        break;
    case 'local':
        $local = new LocalProject($g);
        if ($g->hasArgsWithKey('init')) {
            $local->runInit();
        } elseif ($g->hasArgsWithKey('backup')) {
            $local->runBackup();
        } elseif ($g->hasArgsWithKey('clear')) {
            $local->runClear($g->hasArgsWithKey('y'));
        }
        break;
    case 'sync':
        $sync = new SyncProject($g);
        if ($g->hasArgsWithKey('c')) {
            $sync->runCreate();
        } elseif ($g->hasArgsWithKey('ping')) {
            $sync->runPing();
        } elseif ($g->hasArgsWithKey('u')) { // 上传到远程
            $sync->runUpload($g->getArgsWithKey('u', '*'));
        } elseif ($g->hasArgsWithKey('urm')) { // 从远程删除
            $sync->runRemoteRm($g->getArgsWithKey('urm', '*'));
        } elseif ($g->hasArgsWithKey('d')) {
            $sync->downloadRemote();
        } elseif ($g->hasArgsWithKey('log')) {
            $sync->downloadLogs();
        } elseif ($g->hasArgsWithKey('proj')) { // exam: php admin sync -dir=boyu -proj=house
            $sync->pushProject($g->getArgsWithKey('proj'));
        }
        break;
    default:
        echo "~~~~~~~~~~ HOW TO USE ~~~~~~~~~~", PHP_EOL;
        echo HELP_INFO, PHP_EOL;
        return;
}
if (!empty($g->messages)) {
    echo PHP_EOL;
    print_r($g->messages);
}
echo PHP_EOL;
$g->saveMaintainTmp();