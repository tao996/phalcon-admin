<?php

/*
* Copyright (c) 2024-present
* Author: tao996<lvshutao@outlook.com>
* 
* For the full copyright and license information, please view the LICENSE.txt
* file that was distributed with this source code.
*/

class SyncConfig
{
    /**
     * @var array
     */
    public array $config = SSH_DATA;
    /**
     * 远程项目路径
     * @var string
     */
    public string $remote_phalcon_admin_path = '';
    /**
     * 远程 nginx/conf.d 路径
     * @var string
     */
    public string $remove_nginx_conf_path = '';

    private string $ssh_path;

    public function __construct(string $ssh_path)
    {
        if (!file_exists($ssh_path)) {
            throw new \Exception('ssh.php file not found');
        }
        $this->ssh_path = $ssh_path;
        $this->config = include_once $ssh_path;

        if (empty($this->config['path'])) {
            throw new \Exception('必须指定远程保存地址 ssh.php->path');
        }
        if (empty($this->config['ip'])) {
            throw new \Exception('必须指定 ssh ip 地址' . PHP_EOL);
        } elseif (empty($this->config['username'])) {
            throw new \Exception('必须指定 ssh username' . PHP_EOL);
        } elseif (empty($this->config['password']) && empty($this->config['private_ssh_key'])) {
            throw new \Exception('必须指定登录密码或者登录证书');
        }

        $this->remote_phalcon_admin_path = rtrim($this->config['path'], '/') . '/';
        $this->remove_nginx_conf_path = rtrim($this->config['nginx'] ?? '/etc/nginx/conf.d', '/') . '/';
    }

    public function getConfigWith(string $key, mixed $default = ''): mixed
    {
        return $this->config[$key] ?? $default;
    }

    public function getHosts()
    {
        return $this->config['hosts'] ?? [];
    }

    public function getProjectName(): string
    {
        return basename($this->config['path']);
    }

    // 项目最新上传的时间
    public function getProjectMtime($project)
    {
        $projects = $this->getConfigWith('projects', []);
        return $projects[$project] ?? 0;
    }

    public function updateProjectMtime($project): void
    {
        $projects = $this->getConfigWith('projects', []);
        $projects[$project] = time();
        $this->config['projects'] = $projects;
        $this->updateConfigFile($this->config);
    }

    private function updateConfigFile($data): void
    {
        file_put_contents($this->ssh_path, '<?php return ' . var_export($data, true) . ';');
    }
}