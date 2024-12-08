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
            'php',
            PATH_ROOT . 'artisan',
            $cmd,
        ]);
        exec($cmd, $output);
        return end($output);
    }
}