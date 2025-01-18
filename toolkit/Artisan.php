<?php

/*
* Copyright (c) 2024-present
* Author: tao996<lvshutao@outlook.com>
* 
* For the full copyright and license information, please view the LICENSE.txt
* file that was distributed with this source code.
*/

class Artisan
{
    public G $g;

    public function __construct(G $g)
    {
        $this->g = $g;
    }

    public function exec(): void
    {
        if ('artisan' != $this->g->argsOptions[0]) {
            throw new \Exception('请检查命令是否正确');
        }
        // 示例： docker exec phalcon-admin-php sh -c 'php artisan p/demo/main'
        $serverName = $this->g->argsOptions['s'] ?? '';
        if (empty($serverName)) {
            $serverName = $_ENV['APP_NAME'] . '-php';
        }
        $arguments = [
            $this->g->argsOptions[1]
        ];
        for ($i = 2; $i < count($this->g->argsOptions); $i++) {
            if (isset($this->g->argsOptions[$i])) {
                $arguments[] = $this->g->argsOptions[$i];
            }
        }
        $cmd = 'docker exec ' . $serverName . ' sh -c "php artisan ' . join(' ', $arguments) . '"';
//        ddd($cmd);
        exec($cmd, $output, $result_code);
        if ($result_code === 0) {
            $this->g->messages = array_filter($output);
        } else {
            throw new \Exception('failed:' . $cmd);
        }
    }
}