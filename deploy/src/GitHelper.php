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
