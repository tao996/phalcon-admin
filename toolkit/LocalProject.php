<?php

/*
* Copyright (c) 2024-present
* Author: tao996<lvshutao@outlook.com>
* 
* For the full copyright and license information, please view the LICENSE.txt
* file that was distributed with this source code.
*/

/**
 * 本地应用管理
 * 用于快速地初始化本地项目
 */
class LocalProject
{
    public G $g;
    private bool $overwrite = false;
    private string $testPrefix = '';

    public function __construct(G $g)
    {
        $this->g = $g;
        $this->overwrite = $g->hasArgsWithKey('y');
        $this->testPrefix = $g->prefix;
    }

    public function runQuickStart(): void
    {
        foreach (
            [
                '.env.example',
                'docker-compose.example.yaml',
                'src/config/config.example.php'
            ] as $file
        ) {
            $to_name = $this->testPrefix . str_replace('.example', '', $file);
            if (!file_exists(PATH_ROOT . $to_name)) {
                if (copy(PATH_ROOT . $file, $to_name)) {
                    $this->g->messages[] = 'success copy file {' . $file . '}';
                }
            } else {
                $this->g->messages[] = 'skip file {' . $file . '}';
            }
        }
        if (PHP_OS_FAMILY === "Linux") {
            if (chmod(PATH_ROOT . 'src/storage', 777)) {
                $this->g->messages[] = 'success chmod 777 src/storage';
            } else {
                $this->g->messages[] = 'fail chmod 777 src/storage';
            }
        }
    }

    /**
     * 创建配置文件
     * @throws Exception
     */
    public function runInit(): void
    {
        $port = $this->g->getArgsWithKey('port', 8071, true);
        if ($port < 1) {
            throw new \Exception('port must not empty; exam -port=8071');
        }
        $this->g->maintainData['port'] = $port;

        $this->runBackup('backup before init the config files');
        $this->copyProjectInitFiles();
        $this->replaceProjectInitFiles();
    }

    private function copyProjectInitFiles(): void
    {
        foreach (PROJECT_INIT_FILES as $file) {
            $example = PATH_ROOT . $file;
            if (!file_exists($example)) {
                throw new \Exception('could not found {' . $example . '}' . PHP_EOL);
            }

            $info = pathinfo($example);
            $to_name = $info['dirname'] . '/' . $this->testPrefix . str_replace('.example', '', $info['basename']);
            $title = str_replace(PATH_ROOT, '', $to_name);

            if (file_exists($to_name)) {
                if (!$this->overwrite) {
                    $this->g->messages[] = 'skip file {' . $title . '}';
                    continue;
                }
            }


            if (copy($example, $to_name)) {
                $this->g->messages[] = 'success copy file {' . $title . '}';
            }
        }
    }

    private function replaceProjectInitFiles(): void
    {
        $replaces = [];
        $g = $this->g;
        // 替换 .env 内容
        $name_env = '.env';
        $replaces[$name_env] = [];
        $port = intval($this->g->maintainData['port']);
        $change_port = $port > 0 && $port != 8071;
        if ($change_port) {
            $replaces[$name_env][] = [
                'OPEN_PORT=8071',
                'OPEN_PORT=' . $port
            ];
        }
        if ($this->g->projectName != 'phalcon-admin') {
            $replaces[$name_env][] = [
                'APP_NAME=phalcon-admin',
                'APP_NAME=' . $this->g->projectName
            ];
        }
        // 替换 docker-compose.yaml
        $name_docker_compose = 'docker-compose.yaml';
        $replaces[$name_docker_compose] = [
            ['.example', '']
        ];

        // nginx/sites/default.conf
        $name_nginx_site_default = 'docker/nginx/sites/default.conf';
        $replaces[$name_nginx_site_default] = [
            ['.example', '']
        ];
        $name_nginx_host = 'docker/nginx/host.conf';
        $replaces[$name_nginx_host] = [
            ['.example', '']
        ];


        foreach ($replaces as $file => $replace) {
            if (!empty($replace)) {
                $path_file = PATH_ROOT . $this->testPrefix . '/' . $file;
                if (file_exists($path_file)) {
                    $file_content = file_get_contents($path_file);
                    foreach ($replace as $r) {
                        $file_content = str_replace($r[0], $r[1], $file_content);
                    }
                    file_put_contents($path_file, $file_content);
                    $this->g->messages[] = 'success replace file {' . $file . '} content';
                } else {
                    $this->g->messages[] = 'skip file no exists {' . $file . '}';
                }
            }
        }
    }

    /**
     * @param bool $filter 是否过滤掉不存在的文件
     * @return array
     */
    private function getBackupTo(bool $filter = false): array
    {
        $files = array_map(function ($file) {
            return str_replace('.example', '', $file);
        }, PROJECT_INIT_FILES);
        return $filter ? array_values(array_filter($files, function ($file) {
            return file_exists(PATH_ROOT . $file);
        })) : $files;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function runBackup(string $message = ''): void
    {
        $backup_files = $this->getBackupTo(true);
        if (empty($backup_files)) {
            $this->g->messages[] = 'no backup files';
        } else {
            $suffix = date('ymdHi');

            $backup_dir = PATH_ADMIN_BACKUP . 'local_' . $suffix . '/';
            if (!file_exists($backup_dir) && mkdir($backup_dir) === false) {
                throw new \Exception('create backup dir fail {' . $backup_dir . '}');
            }
            foreach ($backup_files as $file) {
                if (copy(PATH_ROOT . $file, $backup_dir . str_replace('/', '_', $file))) {
                    $this->g->messages[] = 'success backup file {' . $file . '}';
                } else {
                    $this->g->messages[] = 'fail backup file {' . $file . '}';
                }
            }
            if ($message) {
                $message_file = $backup_dir . 'readme.txt';
                if (file_exists($message_file)) {
                    $message = file_get_contents($message_file) . PHP_EOL . $message;
                }
                file_put_contents($message_file, $message);
            }
        }
    }

    /**
     * 清队备份（会自动备份当前配置文件）
     * @return void
     * @throws Exception
     */
    public function runClear(bool $confirm = false): void
    {
        // 强制备份
        if ($confirm) {
            $this->runBackup('clear the config files');
            foreach ($this->getBackupTo(true) as $file) {
                if (unlink(PATH_ROOT . $file)) {
                    $this->g->messages[] = 'success delete file {' . $file . '}';
                } else {
                    $this->g->messages[] = 'fail delete file {' . $file . '}';
                }
            }
            echo 'clear local config files success', PHP_EOL;
        } else {
            echo '待删除文件列表：', PHP_EOL;
            print_r($this->getBackupTo(true));
            echo 'add -y to confirm delete (auto backup to backup/local_xxx dir)', PHP_EOL;
        }
    }

}