<?php
require_once PATH_ROOT . 'tao996/index.php';

$app = new \Phax\Foundation\Application(PATH_ROOT);
$app->autoloadServices();
return $app;