<?php

/**
 * 项目部署器
 * 
 * 编排项目部署的完整生命周期：
 * - init: 首次部署
 * - upgrade: 更新已有项目
 */
class ProjectDeployer
{
    protected DeploySSH $ssh;
    protected GitHelper $git;
    protected TemplateRenderer $renderer;
    protected DeployConfig $config;
    protected RouterManager $router;
    protected string $routerMode = '';

    public function __construct(DeployConfig $config)
    {
        $this->config = $config;
        $this->ssh = new DeploySSH($config->getSshConfig());
        $this->git = new GitHelper($this->ssh);
        $this->renderer = new TemplateRenderer(deploy_base_path() . '/template');
        $this->router = new RouterManager($this->ssh, $config->getMerged()['router'] ?? []);
    }

    /**
     * 检测 Router 模式（本地缓存优先，否则远程检测）
     */
    protected function detectRouterMode(): string
    {
        // 优先使用本地缓存
        $cacheFile = deploy_base_path() . '/.cache/mode.txt';
        if (file_exists($cacheFile)) {
            $mode = trim(file_get_contents($cacheFile));
            if ($mode === RouterManager::MODE_DOCKER || $mode === RouterManager::MODE_HOST) {
                return $mode;
            }
        }
        // 远程检测
        return $this->router->getRecommendedMode();
    }

    /**
     * 宿主机模式下自动分配端口（从 8071 起递增，可被 server.php 中的 nginxPort 覆盖）
     */
    protected function assignNginxPort(?int $preferredPort = null): int
    {
        if ($preferredPort !== null && $preferredPort > 0) {
            return $preferredPort;
        }
        // 从配置中读取
        $cfg = $this->config->getMerged();
        $assignedPort = $cfg['project']['nginxPort'] ?? 0;
        if ($assignedPort > 0) {
            return $assignedPort;
        }
        // 默认起始端口
        return 8071;
    }

    /**
     * 首次部署项目
     */
    public function init(array $options = []): void
    {
        $projectName = $this->config->getProjectName();
        $projectPath = $this->config->getProjectPath();
        $repo = $this->config->getRepo();
        $branch = $this->config->getBranch();
        $modules = $this->config->getModules();
        $domains = $this->config->getDomains();

        // 检测模式
        $this->routerMode = $options['mode'] ?? $this->detectRouterMode();
        $nginxPort = $this->assignNginxPort($options['nginxPort'] ?? null);

        deploy_log("=== 开始部署项目: {$projectName} ===", 'step');
        deploy_log("Router 模式: {$this->routerMode}", 'info');
        if ($this->routerMode === RouterManager::MODE_HOST) {
            deploy_log("Nginx 端口: {$nginxPort}", 'info');
        }

        try {
            $this->ssh->connect();

            // 1. 准备目录
            deploy_log('步骤 1/7: 准备目录', 'step');
            $this->ssh->ensureDir($projectPath);

            // 2. 克隆主仓库
            deploy_log('步骤 2/7: 克隆主仓库', 'step');
            if (!empty($repo)) {
                $this->git->clone($repo, $projectPath, $branch);
            } else {
                deploy_log('未配置 repo，跳过克隆', 'warn');
                // 创建基本的项目目录结构
                $this->ssh->exec("mkdir -p {$projectPath}/src/App/Modules {$projectPath}/docker/nginx/sites {$projectPath}/docker/php {$projectPath}/docker/log/nginx {$projectPath}/docker/log/php");
            }

            // 3. 克隆子模块
            deploy_log('步骤 3/7: 部署子模块', 'step');
            $this->git->cloneModules($modules, $projectPath);
            $this->git->initSubmodules($projectPath);

            // 4. 生成并上传配置文件
            deploy_log('步骤 4/7: 生成配置文件', 'step');
            $this->renderConfigs($projectPath, $nginxPort);

            // 5. Docker Compose 启动
            deploy_log('步骤 5/7: 启动 Docker 容器', 'step');
            $composeFile = $this->routerMode === RouterManager::MODE_HOST
                ? 'docker-compose.ports.yaml'
                : 'docker-compose.yaml';
            $this->ssh->exec("cd {$projectPath} && docker-compose -f {$composeFile} up -d");

            // 6. 更新 Router
            deploy_log('步骤 6/7: 更新 Router', 'step');
            if (!empty($domains)) {
                $this->router->addDomain($projectName, $domains, false, $this->routerMode, $nginxPort);
            }

            // 7. 执行钩子
            deploy_log('步骤 7/7: 执行钩子命令', 'step');
            $this->runHooks('afterInit', $projectPath);

            deploy_log("=== 项目 {$projectName} 部署完成 ===", 'ok');

        } catch (Exception $e) {
            deploy_log("部署失败: " . $e->getMessage(), 'error');
            $this->ssh->disconnect();
            exit(1);
        }

        $this->ssh->disconnect();
    }

    /**
     * 更新已有项目
     */
    public function upgrade(array $options = []): void
    {
        $projectName = $this->config->getProjectName();
        $projectPath = $this->config->getProjectPath();
        $modules = $this->config->getModules();
        $domains = $this->config->getDomains();

        // 检测模式
        $this->routerMode = $options['mode'] ?? $this->detectRouterMode();
        $nginxPort = $this->assignNginxPort($options['nginxPort'] ?? null);

        deploy_log("=== 开始更新项目: {$projectName} ===", 'step');

        try {
            $this->ssh->connect();

            // 检查项目是否存在
            $exists = $this->ssh->exec("[ -d {$projectPath}/.git ] && echo 'YES' || echo 'NO'", false);
            if (trim($exists) !== 'YES') {
                deploy_log("项目目录不存在或不是 git 仓库: {$projectPath}", 'error');
                deploy_log("请先执行: php deploy init {$projectName}", 'info');
                exit(1);
            }

            // 1. 拉取主仓库
            deploy_log('步骤 1/4: 拉取主仓库代码', 'step');
            $this->git->pull($projectPath);

            // 2. 更新子模块
            deploy_log('步骤 2/4: 更新子模块', 'step');
            $this->git->cloneModules($modules, $projectPath);

            // 3. 重新生成配置
            deploy_log('步骤 3/4: 更新配置文件', 'step');
            $this->renderConfigs($projectPath, $nginxPort);

            // 4. 重启容器
            deploy_log('步骤 4/4: 重启容器', 'step');
            $composeFile = $this->routerMode === RouterManager::MODE_HOST
                ? 'docker-compose.ports.yaml'
                : 'docker-compose.yaml';
            $this->ssh->exec("cd {$projectPath} && docker-compose -f {$composeFile} restart");

            // 如果域名有调整，同步 Router
            if (!empty($domains)) {
                $this->router->addDomain($projectName, $domains, false, $this->routerMode, $nginxPort);
            }

            // 执行钩子
            $this->runHooks('afterUpgrade', $projectPath);

            deploy_log("=== 项目 {$projectName} 更新完成 ===", 'ok');

        } catch (Exception $e) {
            deploy_log("更新失败: " . $e->getMessage(), 'error');
            $this->ssh->disconnect();
            exit(1);
        }

        $this->ssh->disconnect();
    }

    /**
     * 生成项目配置文件
     */
    protected function renderConfigs(string $projectPath, int $nginxPort = 8071): void
    {
        $projectName = $this->config->getProjectName();

        // 构建模板变量
        $vars = array_merge([
            'APP_NAME' => $projectName,
            'PROJECT_NAME' => $projectName,
            'PROJECT_PATH' => $projectPath,
            'NETWORKS_NAME' => 'phalcon-shared',
            'TZ' => 'Asia/Shanghai',
            'DATA_PATH_HOST' => $projectPath . '/docker/storage',
            'NGINX_PORT' => $nginxPort,
        ], $this->config->getEnvOverrides());

        // 根据模式选择 docker-compose 模板
        $composeTemplate = $this->routerMode === RouterManager::MODE_HOST
            ? 'docker-compose.ports.yaml'
            : 'docker-compose.yaml';

        // 渲染并上传各配置文件
        // .env
        $envContent = $this->renderer->render(
            $this->getTemplatePath('.env'),
            $vars
        );
        if (!empty($envContent)) {
            $this->ssh->uploadContent($envContent, $projectPath . '/.env');
            deploy_log('已上传 .env', 'ok');
        }

        // docker-compose.yaml（根据模式选择模板）
        $composeContent = $this->renderer->render(
            $this->getTemplatePath($composeTemplate),
            $vars
        );
        if (!empty($composeContent)) {
            $this->ssh->uploadContent($composeContent, $projectPath . '/' . $composeTemplate);
            deploy_log("已上传 {$composeTemplate}", 'ok');
            // 同时拷贝为 docker-compose.yaml 保持兼容
            $this->ssh->exec("cd {$projectPath} && cp {$composeTemplate} docker-compose.yaml", false);
        }

        // nginx 站点配置
        $nginxContent = $this->renderer->render(
            $this->getTemplatePath('nginx/default.conf'),
            $vars
        );
        if (!empty($nginxContent)) {
            $this->ssh->exec("mkdir -p {$projectPath}/docker/nginx/sites", false);
            $this->ssh->uploadContent($nginxContent, $projectPath . '/docker/nginx/sites/default.conf');
            deploy_log('已上传 nginx/default.conf', 'ok');
        }

        // php.ini（生产环境配置）
        $this->ssh->exec("mkdir -p {$projectPath}/docker/php", false);
        // 尝试先上传自定义 php.ini 模板，如果没有则用原项目的 php.prod.ini
        $phpIniContent = $this->renderer->render(
            $this->getTemplatePath('php/php.ini'),
            $vars
        );
        if (!empty($phpIniContent)) {
            $this->ssh->uploadContent($phpIniContent, $projectPath . '/docker/php/php.ini');
            deploy_log('已上传 php/php.ini', 'ok');
        }

        // MySQL 配置（如果有）
        $myCnfContent = $this->renderer->render(
            $this->getTemplatePath('mysql/my.cnf'),
            $vars
        );
        if (!empty($myCnfContent)) {
            $this->ssh->exec("mkdir -p {$projectPath}/docker/mysql", false);
            $this->ssh->uploadContent($myCnfContent, $projectPath . '/docker/mysql/my.cnf');
            deploy_log('已上传 mysql/my.cnf', 'ok');
        }

        // src/config/config.php
        $configPhpContent = $this->renderer->render(
            $this->getTemplatePath('config.php'),
            $vars
        );
        if (!empty($configPhpContent)) {
            $this->ssh->exec("mkdir -p {$projectPath}/src/config", false);
            $this->ssh->uploadContent($configPhpContent, $projectPath . '/src/config/config.php');
            deploy_log('已上传 config.php', 'ok');
        }

        deploy_log('配置文件生成完成', 'ok');
    }

    /**
     * 执行钩子命令
     */
    protected function runHooks(string $hookName, string $projectPath): void
    {
        $hooks = $this->config->getHooks();
        $commands = $hooks[$hookName] ?? [];

        foreach ($commands as $cmd) {
            // 支持 shell: 前缀
            if (str_starts_with($cmd, 'shell:')) {
                $shellCmd = substr($cmd, 6);
                $this->ssh->exec("cd {$projectPath} && {$shellCmd}");
            } else {
                $this->ssh->exec("cd {$projectPath} && {$cmd}");
            }
        }
    }

    /**
     * 获取模板文件路径
     */
    protected function getTemplatePath(string $name): string
    {
        $path = deploy_base_path() . '/template/' . $name;
        if (file_exists($path)) {
            return $path;
        }
        // 回退到项目自带的 example 文件
        $fallback = deploy_base_path() . '/../../' . $name;
        return $fallback;
    }

    /**
     * 查看项目状态
     */
    public function status(): void
    {
        $projectName = $this->config->getProjectName();
        $projectPath = $this->config->getProjectPath();

        deploy_log("=== 项目状态: {$projectName} ===", 'step');

        try {
            $this->ssh->connect();

            // 检查目录
            $dirExists = $this->ssh->exec("[ -d {$projectPath} ] && echo 'YES' || echo 'NO'", false);
            deploy_log("项目目录存在: " . trim($dirExists), 'info');

            // Docker 容器状态
            deploy_log('容器状态:', 'info');
            $this->ssh->exec("cd {$projectPath} && docker-compose ps 2>/dev/null || echo 'Docker 未运行'");

            // 磁盘使用
            deploy_log('磁盘使用:', 'info');
            $this->ssh->exec("du -sh {$projectPath} 2>/dev/null | awk '{print \$1}'");

        } catch (Exception $e) {
            deploy_log("查询状态失败: " . $e->getMessage(), 'error');
        }

        $this->ssh->disconnect();
    }
}
