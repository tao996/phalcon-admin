<?php

//phpinfo();exit;


define('PATH_ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once '../tao996/Kernel.php';
try {
    \tao996\Kernel::with(PATH_ROOT)
        ->setupDisplayErrors()
        ->createApplication()
        ->runWeb();
} catch (\Exception $e) {
    echo $e->getMessage();
    return;
}
