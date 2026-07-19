<?php
/*
1。 新建目录 xxx 并进入目录
2。 [可选]composer init 创建 composer 文件
	如果没有这一步，则第3步需要选择 no，否则会安装到上级目录
3。 安装插件如 composer require w7corp/easywechat:^6.7
4。 执行 php package.php xxx 完成打包


--------- phar
1. php.ini
[Phar]
;phar.readonly = On 修改
phar.readonly = off
*/
if (!isset($argv[1])) {
    die('命令错误：必须指定打包的目录名'.PHP_EOL);
}

$argv[1] = str_replace('\\','/',$argv[1]);

$packageName = rtrim($argv[1],'/');
// 当前脚本所在目录
$pathCMD = str_replace('\\', '/', getcwd());


// 指定了目录
if (isset($argv[2])){
    $pathVendor = $pathCMD .'/'. $argv[2];
} else {
    $pathVendor = $pathCMD . '/' . $packageName . '/vendor';
    if (!is_dir($pathVendor)) {
        die('没有发现待打包目录:' . $pathVendor.PHP_EOL);
    }
}

$pathVendorAutoload = $pathVendor . '/autoload.php';
$pathVendorIndex = $pathVendor . '/index.php';

if (!file_exists($pathVendorAutoload) && !file_exists($pathVendorIndex)) {
    die('文件不存在:' . $pathVendorAutoload.PHP_EOL);
}

if (!file_exists($pathVendorIndex)) {
    if (!copy($pathVendorAutoload, $pathVendorIndex)){
        die('复制文件错误'.PHP_EOL);
    }
}

$pharName = $packageName.'.phar';
$phar = new Phar($pharName);
$phar->buildFromDirectory($pathVendor);
$phar->stopBuffering();

echo 'Phar archieve create success',PHP_EOL;