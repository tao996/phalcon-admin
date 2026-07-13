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
        // 优先使用本地缓存（含服务器指纹校验）
        $cache = get_server_cache();
        $mode = $cache['mode'] ?? '';
        if ($mode === RouterManager::MODE_DOCKER || $mode === RouterManager::MODE_HOST) {
            return $mode;
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
    public function init(array $options = [], bool $useLocalConfigs = false): void
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
            if ($useLocalConfigs) {
                $this->uploadLocalConfigs($projectPath, $nginxPort);
            } else {
                $this->renderConfigs($projectPath, $nginxPort);
            }

            // 5. Docker Compose 启动
            deploy_log('步骤 5/7: 启动 Docker 容器', 'step');
            $composeFile = $this->routerMode === RouterManager::MODE_HOST
                ? 'docker-compose.ports.yaml'
                : 'docker-compose.yaml';
            $this->ssh->exec("cd {$projectPath} && " . get_compose_cmd() . " -f {$composeFile} up -d");

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
     * 预览生成配置文件（不连接远程，仅输出到本地项目目录供检查）
     */
    public function preview(array $options = []): void
    {
        $projectName = $this->config->getProjectName();

        // 检测模式：参数 > 本地缓存 > 默认 host_nginx（预览模式不连远程检测）
        if (isset($options['mode'])) {
            $this->routerMode = $options['mode'];
        } else {
            $cache = get_server_cache();
            $mode = $cache['mode'] ?? '';
            if ($mode === RouterManager::MODE_DOCKER || $mode === RouterManager::MODE_HOST) {
                $this->routerMode = $mode;
            }
        }
        if (empty($this->routerMode)) {
            $this->routerMode = RouterManager::MODE_HOST;
            deploy_log('未检测到模式缓存，默认使用 host_nginx', 'warn');
        }

        $nginxPort = $this->assignNginxPort($options['nginxPort'] ?? null);

        deploy_log("=== 预览模式: {$projectName} ===", 'step');
        deploy_log("Router 模式: {$this->routerMode}", 'info');
        if ($this->routerMode === RouterManager::MODE_HOST) {
            deploy_log("Nginx 端口: {$nginxPort}", 'info');
        }
        deploy_log('', '');

        $localDir = $this->getLocalProjectDir();
        $this->ensureLocalDir($localDir);

        // 构建模板变量
        $dockerImages = $this->config->getMerged()['docker']['images'] ?? [];
        $projectPath = $this->config->getProjectPath();
        $vars = array_merge([
            'APP_NAME' => $projectName,
            'PROJECT_NAME' => $projectName,
            'PROJECT_PATH' => $projectPath,
            'NETWORKS_NAME' => 'phalcon-shared',
            'TZ' => 'Asia/Shanghai',
            'DATA_PATH_HOST' => $projectPath . '/docker/storage',
            'NGINX_PORT' => $nginxPort,
            'MYSQL_USER' => $projectName,
            // 镜像地址（可从 server.php docker.images 覆盖）
            'NGINX_IMAGE' => $dockerImages['nginx'] ?? '',
            'PHP_IMAGE' => $dockerImages['php'] ?? '',
            'MYSQL_IMAGE' => $dockerImages['mysql'] ?? '',
            'REDIS_IMAGE' => $dockerImages['redis'] ?? '',
        ], $this->config->getEnvOverrides());

        // 合并应用配置覆盖（嵌套数组，直接注入 config.php 模板）
        $vars = array_merge($vars, [
            'CONFIG_OVERRIDES' => $this->getConfigOverridesArray(),
        ]);

        $composeTemplate = $this->routerMode === RouterManager::MODE_HOST
            ? 'docker-compose.ports.yaml'
            : 'docker-compose.yaml';

        // 渲染并写入本地文件
        $files = [
            '.env' => $this->getTemplatePath('.env.example'),
            $composeTemplate => deploy_base_path() . '/template/' . $composeTemplate,
            'docker/nginx/sites/default.conf' => $this->getTemplatePath('nginx/default.conf'),
            'docker/php/php.ini' => $this->getTemplatePath('php/php.ini'),
            'docker/mysql/my.cnf' => $this->getTemplatePath('mysql/my.cnf'),
            'src/config/config.php' => $this->getTemplatePath('config.php.template'),
        ];

        foreach ($files as $relativePath => $templatePath) {
            $content = $this->renderer->render($templatePath, $vars);
            if (!empty($content)) {
                $targetFile = $localDir . '/' . $relativePath;
                $targetDir = dirname($targetFile);
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                file_put_contents($targetFile, $content);
                deploy_log("  生成: {$relativePath}", 'ok');
            }
        }

        deploy_log('', '');
        deploy_log("配置文件已生成到: {$localDir}", 'ok');
        deploy_log("请检查配置文件后执行: php deploy init {$projectName} -y", 'info');
    }

    /**
     * 启动/重启项目 Docker 容器（up -d 幂等：首次拉取镜像，后续等同于重启）
     */
    public function restart(string $service = ''): void
    {
        $projectName = $this->config->getProjectName();
        $projectPath = $this->config->getProjectPath();

        $label = $service ? "({$service})" : '';
        deploy_log("=== 重启容器: {$projectName} {$label} ===", 'step');

        try {
            $this->ssh->connect();
            $this->routerMode = $this->detectRouterMode();
            deploy_log("Router 模式: {$this->routerMode}", 'info');

            $composeFile = $this->routerMode === RouterManager::MODE_HOST
                ? 'docker-compose.ports.yaml'
                : 'docker-compose.yaml';

            if ($service) {
                $this->ssh->exec("cd {$projectPath} && " . get_compose_cmd() . " -f {$composeFile} restart {$service}");
            } else {
                $this->ssh->exec("cd {$projectPath} && " . get_compose_cmd() . " -f {$composeFile} up -d");
            }

            deploy_log("=== 容器 {$label}启动完成 ===", 'ok');
        } catch (Exception $e) {
            deploy_log("重启失败: " . $e->getMessage(), 'error');
        }

        $this->ssh->disconnect();
    }

    /**
     * 推送本地配置文件到远程（覆盖已有配置，不重启容器）
     */
    public function pushConfig(): void
    {
        $projectName = $this->config->getProjectName();
        $projectPath = $this->config->getProjectPath();

        // 检测模式（使用本地缓存）
        $this->routerMode = $this->detectRouterMode();
        $nginxPort = $this->assignNginxPort();

        deploy_log("=== 推送配置: {$projectName} ===", 'step');
        deploy_log("Router 模式: {$this->routerMode}", 'info');
        if ($this->routerMode === RouterManager::MODE_HOST) {
            deploy_log("Nginx 端口: {$nginxPort}", 'info');
        }

        try {
            $this->ssh->connect();
            $this->uploadLocalConfigs($projectPath, $nginxPort);
            deploy_log("=== 配置推送完成 ===", 'ok');
        } catch (Exception $e) {
            deploy_log("推送失败: " . $e->getMessage(), 'error');
        }

        $this->ssh->disconnect();
    }

    /**
     * 仅拉取代码（不更新配置，不重启容器）
     */
    public function upgradeCodeOnly(): void
    {
        $projectName = $this->config->getProjectName();
        $projectPath = $this->config->getProjectPath();
        $modules = $this->config->getModules();

        deploy_log("=== 更新代码: {$projectName} ===", 'step');

        try {
            $this->ssh->connect();

            // 检查项目是否存在
            $exists = $this->ssh->exec("[ -d {$projectPath}/.git ] && echo 'YES' || echo 'NO'", false);
            if (trim($exists) !== 'YES') {
                deploy_log("项目目录不存在或不是 git 仓库: {$projectPath}", 'error');
                deploy_log("请先执行: php deploy app:init {$projectName} -y", 'info');
                exit(1);
            }

            // 1. 拉取主仓库
            deploy_log('拉取主仓库代码', 'step');
            $this->git->pull($projectPath);

            // 2. 更新子模块
            deploy_log('更新子模块', 'step');
            $this->git->cloneModules($modules, $projectPath);

            deploy_log("=== 代码更新完成: {$projectName} ===", 'ok');

        } catch (Exception $e) {
            deploy_log("更新失败: " . $e->getMessage(), 'error');
            $this->ssh->disconnect();
            exit(1);
        }

        $this->ssh->disconnect();
    }

    /**
     * 重置远程代码到最新提交（丢弃主仓库和模块目录的全部修改）
     */
    public function reset(): void
    {
        $projectName = $this->config->getProjectName();
        $projectPath = $this->config->getProjectPath();
        $modules = $this->config->getModules();

        deploy_log("=== 重置代码: {$projectName} ===", 'step');
        deploy_log("警告: 将丢弃主仓库和模块目录的已跟踪文件修改（不会删除配置文件）！", 'warn');

        try {
            $this->ssh->connect();

            // 检查项目是否存在
            $exists = $this->ssh->exec("[ -d {$projectPath}/.git ] && echo 'YES' || echo 'NO'", false);
            if (trim($exists) !== 'YES') {
                deploy_log("项目目录不存在或不是 git 仓库: {$projectPath}", 'error');
                exit(1);
            }

            // 1. 重置主仓库
            deploy_log('重置主仓库', 'step');
            $this->ssh->exec("cd {$projectPath} && git reset --hard");

            // 2. 重置子模块
            foreach ($modules as $moduleName => $moduleRepo) {
                $modulePath = $projectPath . '/src/App/Modules/' . $moduleName;
                $moduleExists = $this->ssh->exec("[ -d {$modulePath}/.git ] && echo 'YES' || echo 'NO'", false);
                if (trim($moduleExists) === 'YES') {
                    deploy_log("重置模块: {$moduleName}", 'step');
                    $this->ssh->exec("cd {$modulePath} && git reset --hard");
                }
            }

            deploy_log("=== 代码重置完成: {$projectName} ===", 'ok');

        } catch (Exception $e) {
            deploy_log("重置失败: " . $e->getMessage(), 'error');
            $this->ssh->disconnect();
            exit(1);
        }

        $this->ssh->disconnect();
    }

    /**
     * 推送分析脚本到远程
     */
    public function pushScripts(): void
    {
        $projectName = $this->config->getProjectName();
        $projectPath = $this->config->getProjectPath();
        $scriptsDir = deploy_base_path() . '/scripts';

        deploy_log("=== 推送脚本: {$projectName} ===", 'step');

        try {
            $this->ssh->connect();

            $remoteDir = $projectPath . '/deploys/scripts';
            $this->ssh->exec("mkdir -p {$remoteDir}", false);

            $files = glob($scriptsDir . '/*.sh');
            foreach ($files as $localFile) {
                $name = basename($localFile);
                $remoteFile = $remoteDir . '/' . $name;
                $this->ssh->uploadContent(file_get_contents($localFile), $remoteFile);
                $this->ssh->exec("chmod +x {$remoteFile}", false);
                deploy_log("已上传: {$name}", 'ok');
            }

            deploy_log("=== 脚本推送完成 ===", 'ok');
        } catch (Exception $e) {
            deploy_log("推送失败: " . $e->getMessage(), 'error');
        }

        $this->ssh->disconnect();
    }

    /**
     * 远程执行分析脚本
     */
    public function runScript(string $scriptName, array $args = []): void
    {
        $projectName = $this->config->getProjectName();
        $projectPath = $this->config->getProjectPath();

        $scriptPath = $projectPath . '/deploys/scripts/' . $scriptName . '.sh';

        deploy_log("=== 执行脚本: {$scriptName} ===", 'step');

        try {
            $this->ssh->connect();

            // 拼装参数
            $argStr = '';
            foreach ($args as $a) {
                $argStr .= ' ' . escapeshellarg($a);
            }

            $this->ssh->exec("bash {$scriptPath}{$argStr}");

        } catch (Exception $e) {
            deploy_log("执行失败: " . $e->getMessage(), 'error');
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
            $this->ssh->exec("cd {$projectPath} && " . get_compose_cmd() . " -f {$composeFile} restart");

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
        $dockerImages = $this->config->getMerged()['docker']['images'] ?? [];
        $vars = array_merge([
            'APP_NAME' => $projectName,
            'PROJECT_NAME' => $projectName,
            'PROJECT_PATH' => $projectPath,
            'NETWORKS_NAME' => 'phalcon-shared',
            'TZ' => 'Asia/Shanghai',
            'DATA_PATH_HOST' => $projectPath . '/docker/storage',
            'NGINX_PORT' => $nginxPort,
            'MYSQL_USER' => $projectName,
            // 镜像地址（可从 server.php docker.images 覆盖）
            'NGINX_IMAGE' => $dockerImages['nginx'] ?? '',
            'PHP_IMAGE' => $dockerImages['php'] ?? '',
            'MYSQL_IMAGE' => $dockerImages['mysql'] ?? '',
            'REDIS_IMAGE' => $dockerImages['redis'] ?? '',
        ], $this->config->getEnvOverrides());

        // 合并应用配置覆盖（嵌套数组，直接注入 config.php 模板）
        $vars = array_merge($vars, [
            'CONFIG_OVERRIDES' => $this->getConfigOverridesArray(),
        ]);

        // 根据模式选择 docker-compose 模板
        $composeTemplate = $this->routerMode === RouterManager::MODE_HOST
            ? 'docker-compose.ports.yaml'
            : 'docker-compose.yaml';

        // 渲染并上传各配置文件
        // .env
        $envContent = $this->renderer->render(
            $this->getTemplatePath('.env.example'),
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
            $this->getTemplatePath('config.php.template'),
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
     * 获取本地项目配置目录
     */
    protected function getLocalProjectDir(): string
    {
        return deploy_base_path() . '/projects/' . $this->config->getProjectName();
    }

    /**
     * 确保本地项目配置目录存在
     */
    protected function ensureLocalDir(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * 将项目配置覆盖（server.php 的 config 段）转为嵌套数组的 PHP 代码
     * 自动展开内部的点键（如 jwt.secret → jwt→secret）
     */
    protected function getConfigOverridesArray(): string
    {
        return var_export($this->expandDotKeys($this->config->getConfigOverrides()), true);
    }

    /**
     * 递归展开数组中的点键名
     */
    protected function expandDotKeys(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = $this->expandDotKeys($value);
            }
            if (str_contains($key, '.')) {
                $keys = explode('.', $key);
                $current = &$result;
                foreach ($keys as $k) {
                    if (!isset($current[$k])) {
                        $current[$k] = [];
                    }
                    $current = &$current[$k];
                }
                $current = $value;
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * 上传本地配置文件到远程（供 -y 模式使用）
     * 优先读取本地已生成的文件，不存在时回退到模板渲染
     */
    protected function uploadLocalConfigs(string $projectPath, int $nginxPort): void
    {
        $localDir = $this->getLocalProjectDir();

        // 本地文件路径 => 远程路径的映射
        $fileMap = [
            '.env' => $projectPath . '/.env',
            'docker-compose.yaml' => $projectPath . '/docker-compose.yaml',
            'docker-compose.ports.yaml' => $projectPath . '/docker-compose.ports.yaml',
            'docker/nginx/sites/default.conf' => $projectPath . '/docker/nginx/sites/default.conf',
            'docker/php/php.ini' => $projectPath . '/docker/php/php.ini',
            'docker/mysql/my.cnf' => $projectPath . '/docker/mysql/my.cnf',
            'src/config/config.php' => $projectPath . '/src/config/config.php',
        ];

        $foundAny = false;
        foreach ($fileMap as $localRelative => $remotePath) {
            $localFile = $localDir . '/' . $localRelative;
            if (file_exists($localFile)) {
                $foundAny = true;
                // 确保远程目录存在
                $remoteDir = dirname($remotePath);
                if ($remoteDir !== '.' && $remoteDir !== $projectPath) {
                    $this->ssh->exec("mkdir -p {$remoteDir}", false);
                }
                $this->ssh->uploadContent(file_get_contents($localFile), $remotePath);
                deploy_log("已上传: {$localRelative}", 'ok');
            }
        }

        if (!$foundAny) {
            // 没有本地配置文件，回退到模板渲染
            deploy_log('未找到本地配置文件，使用模板生成', 'warn');
            $this->renderConfigs($projectPath, $nginxPort);
        } else {
            // 确保 docker-compose.yaml 兼容副本
            $this->ssh->exec(
                "cd {$projectPath} && [ -f docker-compose.ports.yaml ] && [ ! -f docker-compose.yaml ] && cp docker-compose.ports.yaml docker-compose.yaml || true",
                false
            );
            deploy_log('配置文件上传完成', 'ok');
        }
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
        $fallback = deploy_base_path() . '/../' . $name;
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
            $this->ssh->exec("cd {$projectPath} && " . get_compose_cmd() . " ps 2>/dev/null || echo 'Docker 未运行'");

            // 磁盘使用
            deploy_log('磁盘使用:', 'info');
            $this->ssh->exec("du -sh {$projectPath} 2>/dev/null | awk '{print \$1}'");

        } catch (Exception $e) {
            deploy_log("查询状态失败: " . $e->getMessage(), 'error');
        }

        $this->ssh->disconnect();
    }

    /**
     * 查看项目 Docker 容器日志
     */
    public function dcLog(string $service = ''): void
    {
        $projectName = $this->config->getProjectName();
        $projectPath = $this->config->getProjectPath();

        $this->routerMode = $this->detectRouterMode();

        $label = $service ? "({$service})" : '';
        deploy_log("=== 容器日志: {$projectName} {$label} ===", 'step');

        try {
            $this->ssh->connect();

            $composeFile = $this->routerMode === RouterManager::MODE_HOST
                ? 'docker-compose.ports.yaml'
                : 'docker-compose.yaml';

            $svcArg = $service ? " {$service}" : '';
            $this->ssh->exec("cd {$projectPath} && " . get_compose_cmd() . " -f {$composeFile} logs --tail=50{$svcArg}");

        } catch (Exception $e) {
            deploy_log("获取日志失败: " . $e->getMessage(), 'error');
        }

        $this->ssh->disconnect();
    }
}
