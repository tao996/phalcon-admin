<?php

/*
* Copyright (c) 2024-present
* Author: tao996<lvshutao@outlook.com>
* 
* For the full copyright and license information, please view the LICENSE.txt
* file that was distributed with this source code.
*/

class DockerService
{
    public G $g;

    public function __construct(G $g)
    {
        $this->g = $g;
    }

    /**
     * 待执行服务名称
     * @return string
     */
    private function getServiceName(string $name): string
    {
        $serverName = $this->g->argsOptions['s'] ?? '';
        if (empty($serverName)) {
            $serverName = $_ENV['APP_NAME'] . '-' . $name;
        }
        return $serverName;
    }

    /**
     * 执行参数
     * @return array
     */
    private function getArguments(): array
    {
        $arguments = [
            $this->g->argsOptions[1]
        ];
        for ($i = 2; $i < count($this->g->argsOptions); $i++) {
            if (isset($this->g->argsOptions[$i])) {
                $arguments[] = $this->g->argsOptions[$i];
            }
        }
        return $arguments;
    }

    /**
     * 执行命令
     * @param string $cmd
     * @return void
     * @throws Exception
     */
    private function exec(string $cmd)
    {
        exec($cmd, $output, $result_code);
        if ($result_code === 0) {
            $this->g->messages = array_filter($output);
        } else {
            throw new \Exception('failed:' . $cmd);
        }
    }

    // 示例： docker exec phalcon-admin-php sh -c 'php artisan p/demo/main'
    public function artisan(): void
    {
        $serverName = $this->getServiceName('php');
        $arguments = $this->getArguments();
        $cmd = 'docker exec ' . $serverName . ' sh -c "php artisan ' . join(' ', $arguments) . '"';
        $this->exec($cmd);
    }

    private string $path_nginx_config;

    /**
     * 执行 nginx 命令
     * @return void
     */
    public function nginx()
    {
        $serverName = $this->getServiceName('nginx');
        $arguments = $this->getArguments();
//        pr($serverName, $arguments, 'end');
        if ('ws' === $arguments[0]) {
            // 1. 从 docker-compose.yaml 中读取配置文件
            \Phax\Utils\MyFileSystem::readWithLines(PATH_ROOT . 'docker-compose.yaml', function ($line) {
                if (str_ends_with($line, ':/etc/nginx/conf.d/default.conf')) {
                    $this->path_nginx_config = str_replace(':/etc/nginx/conf.d/default.conf', '', $line);
                    return false;
                }
            });
            // 2. 读取配置文件内容
            if (empty($this->path_nginx_config)) {
                throw new \Exception("could not find the nginx service config file from docker-compose.yaml");
            }

            preg_match('|sites/(.+)\.conf|', $this->path_nginx_config, $matches);
            // $matches = ['sites/default.example.conf', 'default.example']

            if (empty($matches[0])) {
                throw new \Exception('nginx config file not found in docker dir');
            }
            $path_nginx_config = PATH_ROOT . 'docker/nginx/' . $matches[0];
            $backup_nginx_config = str_replace(
                $matches[1],
                $matches[1] . '.' . date('Ymd') . '.conf',
                $path_nginx_config
            );
            if (!file_exists($path_nginx_config)) {
                throw new \Exception("could not find the nginx service config file from $path_nginx_config");
            }
            // 3. 切换 ws 配置
            // 包含两个 # workerman 注释，分别标记开始和结束位置
            $position = ['start' => 0, 'end' => 0, 'line' => 0];
            \Phax\Utils\MyFileSystem::readWithLines($path_nginx_config, function ($line) use (&$position, &$lines) {
                $position['line'] += 1;
                if (str_contains($line, '# workerman')) {
                    if ($position['start'] === 0) {
                        $position['start'] = $position['line'] + 1;
                    } elseif ($position['end'] === 0) {
                        $position['end'] = $position['line'] - 1;
                        return false;
                    }
                }
            });
            if ($position['start'] < 1 || $position['end'] < 1) {
                throw new \Exception(
                    'could not find the workerman config in nginx config file. the "# workerman" comment is missing'
                );
            }
            // 4. 重新加载配置文件
            $lines = [];
            $position['line'] = 0;
            \Phax\Utils\MyFileSystem::readWithLines($path_nginx_config, function ($line) use (&$lines, &$position) {
//                ddd($position);
                $position['line'] += 1;
                if ($position['start'] <= $position['line'] && $position['line'] <= $position['end']) {
                    if (str_contains($line, '#')) {
                        $lines[] = str_replace('#', '', $line);
                    } else {
                        $lines[] = '#' . $line;
                    }
                } else {
                    $lines[] = $line;
                }
            });

//            ddd(join("\n", $lines));
            if (!file_exists($backup_nginx_config)) {
                copy($path_nginx_config, $backup_nginx_config);
            }
            file_put_contents($path_nginx_config, join("\n", $lines));
            sleep(1);
            $cmd = 'docker exec ' . $serverName . ' sh -c "nginx -s reload"';
            $this->exec($cmd);
        }
    }
}