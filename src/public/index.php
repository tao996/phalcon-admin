<?php

//phpinfo();exit;


define('PATH_ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once PATH_ROOT . 'Kernel.php';
try {
    Kernel::with(PATH_ROOT)
        ->setupDisplayErrors()
        ->createApplication()
        ->runWeb();
} catch (\Exception $e) {
    echo $e->getMessage();
    return;
}
