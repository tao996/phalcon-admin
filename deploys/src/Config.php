<?php

/**
 * 配置加载器
 * 
 * 读取并合并服务器配置 + 项目配置
 * 配置层级：deploys/config.php（全局默认）→ deploys/server.php（服务器连接配置）→ deploys/projects/{name}/server.php（项目配置）
 */
class DeployConfig
{
    protected array $server = [];
    protected array $project = [];
    protected string $projectName = '';

    /**
     * 加载全局服务器配置
     */
    public function loadServer(string $path = ''): array
    {
        if (empty($path)) {
            $path = deploy_base_path() . '/server.php';
        }
        if (!file_exists($path)) {
            deploy_log("服务器配置文件不存在: $path", 'error');
            exit(1);
        }
        $this->server = require $path;
        return $this->server;
    }

    /**
     * 加载项目配置
     */
    public function loadProject(string $name): array
    {
        $this->projectName = safe_name($name);
        $path = deploy_base_path() . '/projects/' . $this->projectName . '/server.php';
        if (!file_exists($path)) {
            deploy_log("项目配置文件不存在: $path", 'error');
            deploy_log("请先创建或拷贝: cp deploys/projects/.example/server.php deploys/projects/{$name}/server.php", 'info');
            exit(1);
        }
        $this->project = require $path;
        return $this->project;
    }

    /**
     * 获取完整的合并配置（服务器配置 + 项目配置合并）
     */
    public function getMerged(): array
    {
        return [
            'ssh' => array_merge_deep(
                $this->server['ssh'] ?? [],
                $this->project['ssh'] ?? []
            ),
            'project' => array_merge_deep(
                $this->server['project'] ?? [],
                $this->project['project'] ?? []
            ),
            'domains' => $this->project['domains'] ?? [],
            'env' => array_merge_deep(
                $this->server['env'] ?? [],
                $this->project['env'] ?? []
            ),
            'config' => array_merge_deep(
                $this->server['config'] ?? [],
                $this->project['config'] ?? []
            ),
            'hooks' => $this->project['hooks'] ?? [],
            'docker' => array_merge_deep(
                $this->server['docker'] ?? [],
                $this->project['docker'] ?? []
            ),
            'router' => array_merge_deep(
                $this->server['router'] ?? [],
                $this->project['router'] ?? []
            ),
        ];
    }

    /**
     * 获取项目名
     */
    public function getProjectName(): string
    {
        return $this->projectName;
    }

    /**
     * 获取 SSH 配置
     */
    public function getSshConfig(): array
    {
        return $this->getMerged()['ssh'];
    }

    /**
     * 获取项目在远程服务器的路径
     */
    public function getProjectPath(): string
    {
        $cfg = $this->getMerged();
        return $cfg['project']['path'] ?? '/root/projects/' . $this->projectName;
    }

    /**
     * 获取项目仓库地址
     */
    public function getRepo(): string
    {
        $cfg = $this->getMerged();
        return $cfg['project']['repo'] ?? '';
    }

    /**
     * 获取项目分支
     */
    public function getBranch(): string
    {
        $cfg = $this->getMerged();
        return $cfg['project']['branch'] ?? 'main';
    }

    /**
     * 获取子模块列表
     */
    public function getModules(): array
    {
        $cfg = $this->getMerged();
        return $cfg['project']['modules'] ?? [];
    }

    /**
     * 获取域名列表
     */
    public function getDomains(): array
    {
        $cfg = $this->getMerged();
        return $cfg['domains'] ?? [];
    }

    /**
     * 获取环境变量覆盖
     */
    public function getEnvOverrides(): array
    {
        $cfg = $this->getMerged();
        return $cfg['env'] ?? [];
    }

    /**
     * 获取应用配置覆盖
     */
    public function getConfigOverrides(): array
    {
        $cfg = $this->getMerged();
        return $cfg['config'] ?? [];
    }

    /**
     * 获取钩子命令
     */
    public function getHooks(): array
    {
        $cfg = $this->getMerged();
        return $cfg['hooks'] ?? [];
    }
}
