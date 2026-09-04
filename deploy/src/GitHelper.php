<?php

/**
 * 远程 Git 操作封装
 */
class GitHelper
{
    public function __construct(protected DeploySSH $ssh)
    {
    }

    /**
     * 在远程服务器上 clone 仓库
     *
     * @param string $repo 仓库地址
     * @param string $path 目标路径
     * @param string $branch 分支名
     */
    public function clone(string $repo, string $path, string $branch = 'main'): void
    {
        $this->ssh->ensureDir(dirname($path));

        $exists = $this->ssh->exec("[ -d {$path}/.git ] && echo 'YES' || echo 'NO'", false);
        if (trim($exists) === 'YES') {
            deploy_log("目录已存在，执行 git pull: {$path}", 'info');
            $this->pull($path);
        } else {
            deploy_log("克隆仓库: {$repo} → {$path} [{$branch}]", 'step');
            $this->ssh->exec("git clone --depth=1 -b {$branch} {$repo} {$path}");
            if (trim($this->ssh->exec("[ -d {$path}/.git ] && echo 'OK' || echo 'FAIL'", false)) === 'OK') {
                deploy_log("仓库克隆完成", 'ok');
            } else {
                deploy_log("仓库克隆失败", 'error');
                exit(1);
            }
        }
    }

    /**
     * 在远程服务器上执行 git pull
     */
    public function pull(string $path): void
    {
        deploy_log("更新代码: {$path}", 'step');
        $this->ssh->exec("cd {$path} && git pull");
    }

    /**
     * clone 子模块到 src/App/Modules/ 目录
     * 
     * @param array $modules ['模块名' => '仓库地址', ...]
     * @param string $projectPath 项目根路径
     */
    public function cloneModules(array $modules, string $projectPath): void
    {
        if (empty($modules)) {
            deploy_log('无子模块需要克隆', 'info');
            return;
        }

        $basePath = $projectPath . '/src/App/Modules';

        foreach ($modules as $name => $repo) {
            $modulePath = $basePath . '/' . $name;
            deploy_log("处理模块: {$name}", 'step');

            $exists = $this->ssh->exec("[ -d {$modulePath}/.git ] && echo 'YES' || echo 'NO'", false);
            if (trim($exists) === 'YES') {
                deploy_log("模块 {$name} 已存在，执行 git pull", 'info');
                $this->ssh->exec("cd {$modulePath} && git pull");
            } else {
                $this->ssh->exec("mkdir -p {$basePath} 2>/dev/null; cd {$basePath} && git clone --depth=1 {$repo} {$name}");
                deploy_log("模块 {$name} 克隆完成", 'ok');
            }
        }
    }

    /**
     * 在远程服务器上初始化 git 子模块（如果项目使用 git submodule）
     */
    public function initSubmodules(string $projectPath): void
    {
        $hasSubmodules = $this->ssh->exec("[ -f {$projectPath}/.gitmodules ] && echo 'YES' || echo 'NO'", false);
        if (trim($hasSubmodules) === 'YES') {
            deploy_log('初始化 git submodule', 'step');
            $this->ssh->exec("cd {$projectPath} && git submodule init && git submodule update --depth=1");
            deploy_log('submodule 更新完成', 'ok');
        }
    }
}
