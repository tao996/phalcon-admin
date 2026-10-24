<?php

namespace Tests\Helper;

class MyTestTaskHelper
{
    /**
     * 执行 php artisan 命令
     * @param string $cmd
     * @return string
     */
    public static function cmd(string $cmd): string
    {
        $cmd = join(' ', [
            // 使用当前 PHP 可执行文件，避免 PATH 中的 php 与项目要求（8.5）不一致
            escapeshellarg(PHP_BINARY),
            escapeshellarg(PATH_ROOT . 'artisan'),
            $cmd,
        ]);
        exec($cmd, $output);
        return end($output);
    }
}