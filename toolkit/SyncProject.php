<?php

/*
* Copyright (c) 2024-present
* Author: tao996<lvshutao@outlook.com>
* 
* For the full copyright and license information, please view the LICENSE.txt
* file that was distributed with this source code.
*/

use Phax\Utils\MyFileSystem;

class SyncProject
{
    /**
     * 默认备份目录名称
     * @var string
     */
    public string $dir = 'default';
    public G $g;

    public function __construct(G $g)
    {
        $this->g = $g;

        $this->dir = $g->getArgsWithKey('dir', 'default');
    }

    /**
     * 当前备份目录路径
     * @param string $suffix 后缀
     * @return string
     */
    private function getSavePath(string $suffix = ''): string
    {
        return PATH_ADMIN_BACKUP . $this->dir . $suffix . '/';
    }

    /**
     * 当前备份配置文件路径
     * @return string
     */
    private function getSshPath(): string
    {
        return $this->getSavePath() . 'ssh.php';
    }


    /**
     * 日志目录
     * @return string
     */
    private function getLogPath(): string
    {
        return PATH_ADMIN_BACKUP . 'logs/' . $this->dir;
    }

    /**
     * 已经将 / 替换为 _
     * @var array
     */
    private array $nginx_files = [];
    /**
     * 已经将 / 替换为 _
     * @var array
     */
    private array $app_files = [];

    private function getMapInitFiles(): array
    {
        $files = [];
//        $project = $this->getProjectName();
        foreach (PROJECT_INIT_FILES as $file) {
            if (str_starts_with($file, 'docker/nginx/host')) {
                $to_file = str_replace('/', '_', str_replace('.example', '', $file));
                $this->nginx_files[] = $to_file;
            } else {
                $to_file = str_replace('/', '_', str_replace('.example', '', $file));
                $this->app_files[] = $to_file;
            }
            $files[$file] = $to_file;
        }
        return $files;
    }

    public function runCreate()
    {
        $dir = $this->getSavePath();
        if (!file_exists($dir)) {
            if (!mkdir($dir)) {
                throw new \Exception('create dir fail {' . $dir . '}');
            }
        }

        $overwrite = $this->g->hasArgsWithKey('y');
        $this->g->messages[] = 'create {' . $dir . '}, overwrite=' . $overwrite;
        foreach ($this->getMapInitFiles() as $file => $copy_file) {
            if (file_exists($dir . $copy_file)) {
                if ($overwrite) {
                    if (copy(PATH_ROOT . $file, $dir . $copy_file)) {
                        $this->createReplace($dir, $copy_file);
                        $this->g->messages[] = 'success overwrite file {' . $copy_file . '}';
                    } else {
                        throw new \Exception('copy file fail {' . $file . '}');
                    }
                } else {
                    $this->g->messages[] = 'skip file {' . $copy_file . '}';
                }
            } else {
                if (copy(PATH_ROOT . $file, $dir . $copy_file)) {
                    $this->createReplace($dir, $copy_file);
                    $this->g->messages[] = 'success create file {' . $copy_file . '}';
                } else {
                    throw new \Exception('copy file fail {' . $file . '}');
                }
            }
        }

        $path_ssh = $this->getSshPath();
        $ssh_data = SSH_DATA;
        if (file_exists($path_ssh)) {
            $ssh_data = array_merge($ssh_data, include_once $path_ssh);
        }
        $this->saveSshData($ssh_data);
        $this->g->messages[] = 'create success';
    }

    private array $replaces = [
        'docker-compose.yaml' => [
            ['/default.example.conf', '/default.conf'],
            ['/php.example.ini', '/php.ini'],
            ['/supervisord.example.conf', '/supervisord.conf']
        ],
        'docker_nginx_sites_default.conf' => [
            ['.example', ''],
        ],
        'src_config_config.php' => [
            ['/services.example.php', '/services.php']
        ]
    ];

    private function createReplace($dir, $copy_file)
    {
        if (isset($this->replaces[$copy_file])) {
            $content = file_get_contents($dir . $copy_file);
            foreach ($this->replaces[$copy_file] as $replace) {
                $content = str_replace($replace[0], $replace[1], $content);
            }
            file_put_contents($dir . $copy_file, $content);
        }
    }

    private function saveSshData(array $data): void
    {
        if (!file_put_contents($this->getSshPath(), '<?php return ' . var_export($data, true) . ';')) {
            throw new \Exception('save ssh data fail');
        }
    }

    public function runPing()
    {
        $cc = new SshConfig($this->getSshPath());
        $ssh = new RemoteCmdManager($this->g, $cc);
        $ssh->ssh2();
        $this->g->messages[] = 'ping success';
    }

    /**
     * 将配置文件上传到远程服务器
     * @param string $part
     * @return void
     * @throws Exception
     */
    public function runUpload(string $part = '*')
    {
        $cc = new SshConfig($this->getSshPath());
        $ssh = new RemoteCmdManager($this->g, $cc);
        $this->getMapInitFiles();
        $localDir = $this->getSavePath(); // 本地目录
        $project = $cc->getProjectName();

        if (in_array($part, ['*', 'app'])) {
            $this->g->messages[] = '准备上传项目配置文件';
            foreach ($this->app_files as $file) {
                $local_file = $localDir . $file;
                // 恢复目录位置
                $remote_file = $cc->remote_phalcon_admin_path . str_replace('_', '/', $file);
                $ssh->sendFile($local_file, $remote_file);
            }
        }
        if (in_array($part, ['*', 'nginx'])) {
            if (empty($ssh->sendCommand('which nginx'))) {
                $this->g->messages[] = '未安装 nginx，请手动安装后再执行';
            } else {
                $this->g->messages[] = '准备上传 nginx 配置文件';
                foreach ($this->nginx_files as $file) {
                    $local_file = $localDir . $file;
                    $remote_file = $cc->remove_nginx_conf_path . str_replace('nginx_host', 'nginx_' . $project, $file);
                    $ssh->sendFile($local_file, $remote_file);
                }

                $this->g->messages[] = '准备上传 ssl 证书';
                foreach ($this->config['certs'] ?? [] as $cert) {
                    $local_file = $localDir . $cert;
                    if (file_exists($local_file)) {
                        $remote_file = $cc->remove_nginx_conf_path . $cert;
                        $ssh->sendFile($local_file, $remote_file);
                    }
                }
            }
        }
        if (in_array($part, ['*', 'hosts'])) {
            $hosts = $cc->getHosts();
            if (!empty($hosts)) {
                $this->g->messages[] = '准备处理 /etc/hosts 文件';
                $content = $ssh->sendCommand('cat /etc/hosts');
                $append_hosts = [];
                foreach ($hosts as $host) {
                    if (!str_contains($content, $host)) {
                        $append_hosts[] = '127.0.0.1 ' . $host;
                    }
                }
                if (!empty($append_hosts)) {
                    $cmd = 'echo "' . join(PHP_EOL, $append_hosts) . PHP_EOL . '" >> /etc/hosts';
                    $ssh->sendCommand($cmd);
                    $this->g->messages[] = '写入完成 /etc/hosts';
                }
            } else {
                $this->g->messages[] = '未指定 hosts，跳过';
            }
        }
    }

    /**
     * 移除远程服务器配置文件
     * @param string $part
     * @return void
     * @throws Exception
     */
    public function runRemoteRm(string $part = '*'): void
    {
        $cc = new SshConfig($this->getSshPath());
        $ssh = new RemoteCmdManager($this->g, $cc);
        $this->getMapInitFiles();
        $project = $cc->getProjectName();

        if (in_array($part, ['*', 'app'])) {
            $this->g->messages[] = '准备移除项目配置文件';
            foreach ($this->app_files as $file) {
                $remote_file = $cc->remote_phalcon_admin_path . str_replace('_', '/', $file);
                $ssh->delFile($remote_file);
            }
        }
        if (in_array($part, ['*', 'nginx'])) {
            if (empty($ssh->sendCommand('which nginx'))) {
                $this->g->messages[] = '未安装 nginx，请手动安装后再执行';
            } else {
                $this->g->messages[] = '准备移除 nginx 配置文件';
                foreach ($this->nginx_files as $file) {
                    $remote_file = $cc->remove_nginx_conf_path . str_replace('nginx_host', 'nginx_' . $project, $file);
                    $ssh->delFile($remote_file);
                }

                $this->g->messages[] = '准备移除 ssl 证书';
                foreach ($this->config['certs'] ?? [] as $cert) {
                    $remote_file = $cc->remove_nginx_conf_path . $cert;
                    $ssh->delFile($remote_file);
                }
            }
        }
        if (in_array($part, ['*', 'hosts'])) {
            $hosts = $cc->getHosts();
            if (!empty($hosts)) {
                $this->g->messages[] = '准备移除 /etc/hosts 域名文件';
                $content = $ssh->sendCommand('cat /etc/hosts');
                if (str_contains($content, '"')) {
                    throw new \Exception('/etc/hosts 中包含双引号，请手动替换');
                }

                foreach ($hosts as $host) {
                    $content = str_replace('127.0.0.1 ' . $host, '', $content);
                }
                $content = preg_replace('/\n{2,}/', "\n", $content);
                $cmd = 'echo "' . $content . '" >> /etc/hosts';
                $ssh->sendCommand($cmd);
                $this->g->messages[] = '更新完成 /etc/hosts';
            } else {
                $this->g->messages[] = '没有需要移除的域名';
            }
        }
    }

    /**
     * 下载远程配置文件到本地
     * @return void
     * @throws Exception
     */
    public function downloadRemote(): void
    {
        $cc = new SshConfig($this->getSshPath());
        $ssh = new RemoteCmdManager($this->g, $cc);
        $this->getMapInitFiles();
        $project = $cc->getProjectName();
        $localDir = $this->getSavePath('_d' . date('ymdHi')); // 本地目录
        if (file_exists($localDir)) {
            if (!mkdir($localDir)) {
                throw new \Exception('创建目录失败:' . $localDir);
            }
        }

        $this->g->messages[] = '准备下载项目配置文件';
        foreach ($this->app_files as $file) {
            $local_file = $localDir . $file;
            $remote_file = $cc->remote_phalcon_admin_path . str_replace('_', '/', $file);
            $ssh->recvFile($remote_file, $local_file);
        }

        if (empty($ssh->sendCommand('which nginx'))) {
            $this->g->messages[] = '未安装 nginx，跳过下载';
        } else {
            $this->g->messages[] = '准备下载 nginx 配置文件';
            foreach ($this->nginx_files as $file) {
                $local_file = $localDir . $file;
                $remote_file = $cc->remove_nginx_conf_path . str_replace('nginx_host', 'nginx_' . $project, $file);
                $ssh->recvFile($local_file, $remote_file);
            }

            $this->g->messages[] = '准备下载 ssl 证书';
            foreach ($this->config['certs'] ?? [] as $cert) {
                $local_file = $localDir . $cert;
                if (file_exists($local_file)) {
                    $remote_file = $cc->remove_nginx_conf_path . $cert;
                    $ssh->recvFile($local_file, $remote_file);
                }
            }
        }
    }

    /**
     * 下载日志
     * @throws Exception
     */
    public function downloadLogs(): void
    {
        $cc = new SshConfig($this->getSshPath());

        if ($logs = $cc->getConfigWith('logs', [])) {
            $this->g->messages[] = '准备下载日志文件';

            $ssh = new RemoteCmdManager($this->g, $cc);
            $localDir = $this->getSavePath('_log' . date('ymdHi')); // 本地目录
            if (!file_exists($localDir)) {
                if (!mkdir($localDir)) {
                    throw new \Exception('创建目录失败:' . $localDir);
                }
            }

            foreach ($logs as $log) {
                if (is_string($log)) {
                    $remote_file = $cc->remote_phalcon_admin_path . $log;
                    $local_file = $localDir . str_replace('/', '_', $log);
                    $ssh->recvFile($remote_file, $local_file);
                } elseif (is_array($log)) {
                    $ssh->downloadDir($cc->remote_phalcon_admin_path . $log[0], $localDir);
                }
            }
        } else {
            $this->g->messages[] = '没有需要下载的日志';
        }
    }

    /**
     * 推送项目到远程
     * @throws Exception
     */
    public function pushProjects(string $proj): void
    {
        $cc = new SshConfig($this->getSshPath());
        if (empty($proj)) {
            $projects = $cc->getConfigWith('projects', []);
            if (empty($projects)) {
                throw new \Exception('could not find any projects in ssh.php');
            }
            $projects = array_keys($projects);
        } else {
            $projects = explode(',', $proj);
        }
        foreach ($projects as $project) {
            $this->pushProject($project, $cc);
        }
    }

    /**
     * 推送本地 App/xxx 到远程目录
     * @param string $proj
     * @return void
     */
    public function pushProject(string $proj, SshConfig $cc): void
    {
        $proj = trim($proj);
        if (empty($proj)) {
            throw new \Exception('必须指定推送的应用');
        }
        $path_project = PATH_SRC . 'App/Projects/' . $proj . '/';
        if (!is_dir($path_project)) {
            throw new \Exception('待推送的项目不存在');
        }

        $this->g->messages[] = '准备推送本地应用:' . $proj;
        $ssh = new RemoteCmdManager($this->g, $cc);

        $gitignore = $path_project . '.gitignore';
        if (file_exists($gitignore)) {
            $patterns = MyFileSystem::generateFilterPatternsByGitignore(file_get_contents($gitignore));
            $files = MyFileSystem::getFilesInDirectory($path_project, function ($path) use ($patterns, $path_project) {
                if (str_contains($path, '.git')) {
                    return true;
                }
                return MyFileSystem::filterByGitignorePatterns($path, $patterns, $path_project);
            });
        } else {
            $files = MyFileSystem::getFilesInDirectory($path_project, function ($path) {
                return str_contains($path, '.git');
            });
        }

        $remote_push_path = $cc->remote_phalcon_admin_path . 'src';
        $last_mtime = $cc->getProjectMtime($proj);
        foreach ($files as $file) {
            $mtime = filemtime($file);
            if ($mtime > $last_mtime) {
                $remote_file = $remote_push_path . '/' . str_replace(PATH_SRC, '', $file);
                $ssh->sendFile($file, $remote_file);
            }
        }
        $cc->updateProjectMtime($proj);
        $this->g->messages[] = '推送完成';

        if ($this->g->hasArgsWithKey('git')) {
            $this->g->messages[] = '准备更新项目 git pull';
            $ssh->sendCommand('cd ' . $cc->remote_phalcon_admin_path . ' && git pull');
            $this->g->messages[] = '更新完成';
        }
    }

}