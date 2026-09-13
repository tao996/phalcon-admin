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
    protected LocalGitSync $localSync;
    protected TemplateRenderer $renderer;
    protected DeployConfig $config;
    protected RouterManager $router;

    public function __construct(DeployConfig $config)
    {
        $this->config = $config;
        $this->ssh = new DeploySSH($config->getSshConfig());
        $this->git = new GitHelper($this->ssh);
        $this->localSync = new LocalGitSync($this->ssh, $this->getLocalRepoRoot());
        $this->renderer = new TemplateRenderer(deploy_base_path() . '/template');
        $this->router = new RouterManager($this->ssh);
    }

    /**
     * 按 sync.items 逐项同步代码/目录（server.php 中 'sync' => ['items' => [...]]）
     *
     * 每项根据 method 分发：
     *   git    — 远程 clone（已存在则 pull）；filesystem 目标不支持
     *   bundle — 本地打包直传远程 fetch/reset；filesystem 目标直接 fetch/reset 本地目录
     *   ftp    — SFTP 增量直传（只增改不删除）；filesystem 目标为本地复制（同样只增改）
     *
     * path 省略表示主仓库；path 为相对项目根的目录，本地源目录 = 本地仓库根 + path
     *
     * @param bool $filesystem 目标是否为本地文件系统
     * @param array $options ['method' => 'git|bundle|ftp' 只执行该方式的条目,
     *                       'full' => true ftp 条目忽略增量清单强制全量]
     */
    protected function runSyncItems(bool $filesystem = false, array $options = []): void
    {
        $methodFilter = (string)($options['method'] ?? '');
        $items = $this->config->getSyncItems();

        if ($methodFilter !== '') {
            $items = array_values(array_filter(
                $items,
                fn(array $item): bool => $item['method'] === $methodFilter
            ));
            if (empty($items)) {
                deploy_log("没有匹配 method={$methodFilter} 的同步项（可用值：git|bundle|ftp）", 'error');
                exit(1);
            }
        }

        if (empty($items)) {
            deploy_log("未配置 sync.items（server.php 中 'sync' => ['items' => [...]]），跳过代码同步", 'warn');
            return;
        }

        $projectName = $this->config->getProjectName();
        $projectPath = $this->config->getProjectPath();
        $sftpSync = null;
        $total = count($items);

        foreach (array_values($items) as $index => $item) {
            $method = $item['method'];
            $path = $item['path'];
            $branch = $item['branch'];
            $isMain = $path === '';
            $label = $isMain ? '主仓库' : $path;
            $step = sprintf('[%d/%d] [%s] %s', $index + 1, $total, $method, $label);

            if ($isMain && $method === 'ftp') {
                deploy_log("{$step}: ftp 方式不支持主仓库，跳过", 'warn');
                continue;
            }

            $remotePath = $isMain ? $projectPath : $projectPath . '/' . $path;
            $localPath = $isMain ? $this->getLocalRepoRoot() : $this->getLocalRepoRoot() . '/' . $path;

            switch ($method) {
                case 'git':
                    if ($filesystem) {
                        deploy_log("{$step}: filesystem 目标不支持 git 方式，跳过", 'warn');
                        break;
                    }
                    if ($item['repo'] === '') {
                        deploy_log("{$step}: 未配置 repo，跳过", 'warn');
                        break;
                    }
                    deploy_log("同步 {$step} ← {$item['repo']} [{$branch}]", 'step');
                    $this->git->clone($item['repo'], $remotePath, $branch);
                    break;

                case 'bundle':
                    if (!is_dir($localPath . '/.git')) {
                        deploy_log("{$step}: 本地仓库不存在，跳过: {$localPath}", 'warn');
                        break;
                    }
                    deploy_log("同步 {$step}（bundle）", 'step');
                    $tag = 'sync-' . safe_name($projectName . '-' . ($isMain ? 'main' : str_replace('/', '-', $path)));
                    if ($filesystem) {
                        $this->localSync->syncLocal($localPath, $remotePath, $branch, $tag);
                    } else {
                        $this->localSync->sync($localPath, $remotePath, $branch, $tag);
                    }
                    break;

                case 'ftp':
                    if (!is_dir($localPath)) {
                        deploy_log("{$step}: 本地目录不存在，跳过: {$localPath}", 'warn');
                        break;
                    }
                    deploy_log("同步 {$step}（ftp）", 'step');
                    if (!empty($options['full'])) {
                        deploy_log('full 模式：忽略清单，强制上传全部文件', 'info');
                    }
                    if ($filesystem) {
                        $this->copyDirToTarget($localPath, $remotePath, $item['excludes']);
                    } else {
                        $sftpSync ??= new SftpDirSync($this->ssh, $this->getLocalRepoRoot(), $projectPath, $projectName);
                        $sftpSync->syncDirs([$path], !empty($options['full']), $item['excludes']);
                    }
                    break;

                default:
                    deploy_log("{$step}: 未知同步方式 '{$method}'，跳过", 'warn');
            }
        }
    }

    /**
     * 本地目录复制（ftp 方式 + filesystem 目标）：目标存在且大小/mtime 未变化时跳过，只增改不删除
     *
     * @param array $excludes 不复制的相对路径前缀（目录或文件）
     */
    protected function copyDirToTarget(string $localDir, string $targetDir, array $excludes = []): void
    {
        $localDir = rtrim($localDir, '/\\');
        $targetDir = rtrim($targetDir, '/\\');
        $count = 0;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($localDir, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $fileInfo) {
            $rel = str_replace('\\', '/', substr($fileInfo->getPathname(), strlen($localDir) + 1));
            $to = $targetDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
            $toParent = dirname($to);
            if (!is_dir($toParent)) {
                mkdir($toParent, 0755, true);
            }
            if (is_file($to)
                && filesize($to) === $fileInfo->getSize()
                && filemtime($to) >= $fileInfo->getMTime()) {
                continue;
            }
            copy($fileInfo->getPathname(), $to);
            touch($to, $fileInfo->getMTime());
            $count++;
        }
        deploy_log("目录复制完成: {$localDir} → {$targetDir}（更新 {$count} 个文件）", 'ok');
    }

    /**
     * 同步目标是否为本地文件系统（不经 SSH/SFTP）
     */
    public function isFilesystemSync(): bool
    {
        return $this->config->getSyncTarget() === 'filesystem';
    }

    /**
     * 本地仓库根目录（deploy 目录的上级）
     */
    protected function getLocalRepoRoot(): string
    {
        return dirname(deploy_base_path());
    }

    /**
     * 本地 nginx server block 文件路径（事实源，与远程 /etc/nginx/conf.d/<项目>.conf 对应）
     */
    protected function getLocalNginxConfFile(): string
    {
        $projectName = $this->config->getProjectName();
        return deploy_base_path() . '/projects/' . $projectName . '/nginx/' . $projectName . '.conf';
    }

    /**
     * 生成本地 nginx server block（内容来自 domains + nginxPort + nginx.ssl 配置）
     * 域名未配置时跳过
     */
    public function writeLocalNginxConf(): void
    {
        $projectName = $this->config->getProjectName();
        $domains = $this->config->getDomains();
        if (empty($domains)) {
            deploy_log('项目未配置域名，跳过 nginx 配置生成', 'warn');
            return;
        }

        $nginxPort = $this->assignNginxPort();
        $content = RouterManager::generateServerBlock(
            $domains,
            '127.0.0.1:' . $nginxPort,
            $this->config->getNginxSsl()
        );

        $localFile = $this->getLocalNginxConfFile();
        $localDir = dirname($localFile);
        if (!is_dir($localDir)) {
            mkdir($localDir, 0755, true);
        }
        $isNew = !file_exists($localFile);
        file_put_contents($localFile, $content);
        deploy_log(($isNew ? '已生成' : '已更新') . "本地 nginx 配置: {$localFile}", 'ok');
    }

    /**
     * 生成本地 nginx 配置并上传到远程 /etc/nginx/conf.d/（自带连接管理）
     */
    public function publishNginxConf(): void
    {
        $projectName = $this->config->getProjectName();
        if (empty($this->config->getDomains())) {
            deploy_log('项目未配置域名，跳过', 'warn');
            return;
        }

        $this->writeLocalNginxConf();
        $content = file_get_contents($this->getLocalNginxConfFile());

        try {
            $this->ssh->connect();
            $this->router->uploadConf($projectName, $content);
        } catch (Exception $e) {
            deploy_log('nginx 配置发布失败: ' . $e->getMessage(), 'error');
        }
        $this->ssh->disconnect();
    }

    /**
     * 项目 nginx 端口（宿主机模式）：server.php 的 project.nginxPort，默认 8071
     */
    protected function assignNginxPort(): int
    {
        $cfg = $this->config->getMerged();
        $assignedPort = $cfg['project']['nginxPort'] ?? 0;
        if ($assignedPort > 0) {
            return (int)$assignedPort;
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
        $domains = $this->config->getDomains();

        $nginxPort = $this->assignNginxPort();

        deploy_log("=== 开始部署项目: {$projectName} ===", 'step');
        deploy_log("Nginx 端口: {$nginxPort}", 'info');

        try {
            $this->ssh->connect();

            // 1. 准备目录
            deploy_log('步骤 1/6: 准备目录', 'step');
            $this->ssh->ensureDir($projectPath);

            // 2. 按 sync.items 同步代码/目录
            deploy_log('步骤 2/6: 同步代码（sync.items）', 'step');
            $this->runSyncItems(false);
            $this->git->initSubmodules($projectPath);

            // 3. 生成并上传配置文件
            deploy_log('步骤 3/6: 生成配置文件', 'step');
            if ($useLocalConfigs) {
                $this->uploadLocalConfigs($projectPath, $nginxPort);
            } else {
                $this->renderConfigs($projectPath, $nginxPort);
            }

            // 4. Docker Compose 启动
            deploy_log('步骤 4/6: 启动 Docker 容器', 'step');
            $composeFile = 'docker-compose.yaml';
            $this->ssh->exec("cd {$projectPath} && " . get_compose_cmd() . " -f {$composeFile} up -d");

            // 5. 更新 Router
            deploy_log('步骤 5/6: 添加 nginx/conf.d/', 'step');
            if (!empty($domains)) {
                $this->writeLocalNginxConf();
                $this->router->uploadConf($projectName, file_get_contents($this->getLocalNginxConfFile()));
            }

            // 6. 执行钩子
            deploy_log('步骤 6/6: 执行钩子命令', 'step');
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
     * 同步到本地文件系统目录（代码 + 可选生成配置），不经 SSH/SFTP
     */
    public function filesystemSync(array $options = [], bool $withConfigs = true): void
    {
        $projectName = $this->config->getProjectName();
        $targetPath = $this->config->getProjectPath();
        $nginxPort = $this->assignNginxPort();

        deploy_log("=== 本地目录同步: {$projectName} → {$targetPath} ===", 'step');
        deploy_log('代码同步: sync.items (filesystem)', 'info');

        // 1. 按 sync.items 同步代码/目录
        $this->runSyncItems(true);

        // 2. 生成配置
        if ($withConfigs) {
            $this->writeLocalConfigs($targetPath, $nginxPort);
        }

        deploy_log("=== 本地目录同步完成: {$targetPath} ===", 'ok');
    }

    /**
     * 构建模板变量（预览 / 远程渲染 / 本地写入共用）
     */
    protected function buildVars(int $nginxPort): array
    {
        $projectName = $this->config->getProjectName();
        $projectPath = $this->config->getProjectPath();
        $dockerImages = $this->config->getMerged()['docker']['images'] ?? [];

        $vars = array_merge([
            'APP_NAME' => $projectName,
            'PROJECT_NAME' => $projectName,
            'PROJECT_PATH' => $projectPath,
            'NETWORKS_NAME' => 'phalcon-shared',
            'TZ' => 'Asia/Shanghai',
            'DATA_PATH_HOST' => str_replace('\\', '/', $projectPath . '/docker/storage'),
            'NGINX_PORT' => $nginxPort,
            'MYSQL_USER' => $projectName,
            'NGINX_IMAGE' => $dockerImages['nginx'] ?? '',
            'PHP_IMAGE' => $dockerImages['php'] ?? '',
            'MYSQL_IMAGE' => $dockerImages['mysql'] ?? '',
            'REDIS_IMAGE' => $dockerImages['redis'] ?? '',
        ], $this->config->getEnvOverrides());

        $vars['CONFIG_OVERRIDES'] = $this->getConfigOverridesArray();

        return $vars;
    }

    /**
     * 将生成的配置写入本地目标目录
     */
    protected function writeLocalConfigs(string $targetDir, int $nginxPort): void
    {
        $vars = $this->buildVars($nginxPort);
        $files = [
            '.env' => $this->getTemplatePath('.env.deploy.example'),
            'docker-compose.yaml' => deploy_base_path() . '/template/docker-compose.yaml',
            'docker/nginx/sites/default.conf' => $this->getTemplatePath('nginx/default.conf'),
            'docker/php/php.ini' => $this->getTemplatePath('php/php.ini'),
            'docker/mysql/my.cnf' => $this->getTemplatePath('mysql/my.cnf'),
            'src/config/config.php' => $this->getTemplatePath('config.php.template'),
        ];

        foreach ($files as $relativePath => $templatePath) {
            $content = $this->renderer->render($templatePath, $vars);
            if ($content === '') {
                continue;
            }
            $targetFile = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR
                . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            $dirName = dirname($targetFile);
            if (!is_dir($dirName)) {
                mkdir($dirName, 0755, true);
            }
            file_put_contents($targetFile, $content);
            deploy_log("  生成: {$relativePath}", 'ok');
        }
    }

    /**
     * 预览生成配置文件（不连接远程，仅输出到本地项目目录供检查）
     */
    public function preview(array $options = []): void
    {
        $projectName = $this->config->getProjectName();

        $nginxPort = $this->assignNginxPort();

        deploy_log("=== 预览模式: {$projectName} ===", 'step');
        deploy_log("Nginx 端口: {$nginxPort}", 'info');
        deploy_log('', '');

        $localDir = $this->getLocalProjectDir();
        $this->ensureLocalDir($localDir);

        // 构建模板变量
        $vars = $this->buildVars($nginxPort);

        // 渲染并写入本地文件
        $files = [
            '.env' => $this->getTemplatePath('.env.deploy.example'),
            'docker-compose.yaml' => deploy_base_path() . '/template/docker-compose.yaml',
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
                if (file_exists($targetFile)) {
                    deploy_log("  跳过: {$relativePath}", 'ok');
                } else {
                    file_put_contents($targetFile, $content);
                    deploy_log("  生成: {$relativePath}", 'ok');
                }
            }
        }

        // 本地 nginx server block（事实源，含 ssl 状态）
        $this->writeLocalNginxConf();

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
            $composeFile = 'docker-compose.yaml';

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

        $nginxPort = $this->assignNginxPort();

        deploy_log("=== 推送配置: {$projectName} ===", 'step');

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
     *
     * 支持 method=git|bundle|ftp 只执行该方式的同步项，full=1 强制 ftp 全量上传
     */
    public function upgradeCodeOnly(array $options = []): void
    {
        $projectName = $this->config->getProjectName();
        $projectPath = $this->config->getProjectPath();

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

            // 按 sync.items 同步代码/目录（支持 method= 过滤）
            $this->runSyncItems(false, $options);

            deploy_log("=== 代码更新完成: {$projectName} ===", 'ok');

        } catch (Exception $e) {
            deploy_log("更新失败: " . $e->getMessage(), 'error');
            $this->ssh->disconnect();
            exit(1);
        }

        $this->ssh->disconnect();
    }

    /**
     * 重置远程代码到最新提交（丢弃主仓库和 git/bundle 同步目录的全部修改）
     */
    public function reset(): void
    {
        $projectName = $this->config->getProjectName();
        $projectPath = $this->config->getProjectPath();

        deploy_log("=== 重置代码: {$projectName} ===", 'step');
        deploy_log("警告: 将丢弃主仓库和 git/bundle 同步目录的已跟踪文件修改（不会删除配置文件）！", 'warn');

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

            // 2. 重置 git/bundle 方式的子目录
            foreach ($this->config->getSyncItems() as $item) {
                $path = $item['path'];
                if ($path === '' || !in_array($item['method'], ['git', 'bundle'], true)) {
                    continue;
                }
                $repoPath = $projectPath . '/' . $path;
                $repoExists = $this->ssh->exec("[ -d {$repoPath}/.git ] && echo 'YES' || echo 'NO'", false);
                if (trim($repoExists) === 'YES') {
                    deploy_log("重置: {$path}", 'step');
                    $this->ssh->exec("cd {$repoPath} && git reset --hard");
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

            $remoteDir = $projectPath . '/deploy/scripts';
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

        $scriptPath = $projectPath . '/deploy/scripts/' . $scriptName . '.sh';

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
        $domains = $this->config->getDomains();

        $nginxPort = $this->assignNginxPort();

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

            // 1. 按 sync.items 同步代码/目录（支持 method= 过滤）
            deploy_log('步骤 1/3: 同步代码（sync.items）', 'step');
            $this->runSyncItems(false, $options);

            // 2. 重新生成配置
            deploy_log('步骤 2/3: 更新配置文件', 'step');
            $this->renderConfigs($projectPath, $nginxPort);

            // 3. 重启容器
            deploy_log('步骤 3/3: 重启容器', 'step');
            $composeFile = 'docker-compose.yaml';
            $this->ssh->exec("cd {$projectPath} && " . get_compose_cmd() . " -f {$composeFile} restart");

            // 如果域名有调整，同步 Router
            if (!empty($domains)) {
                $this->writeLocalNginxConf();
                $this->router->uploadConf($projectName, file_get_contents($this->getLocalNginxConfFile()));
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
        $vars = $this->buildVars($nginxPort);

        // 根据模式选择 docker-compose 模板
        // 渲染并上传各配置文件
        // .env
        $envContent = $this->renderer->render(
            $this->getTemplatePath('.env.deploy.example'),
            $vars
        );
        if (!empty($envContent)) {
            $this->ssh->uploadContent($envContent, $projectPath . '/.env');
            deploy_log('已上传 .env', 'ok');
        }

        // docker-compose.yaml（统一模板，含 127.0.0.1 端口映射）
        $composeContent = $this->renderer->render(
            $this->getTemplatePath('docker-compose.yaml'),
            $vars
        );
        if (!empty($composeContent)) {
            $this->ssh->uploadContent($composeContent, $projectPath . '/docker-compose.yaml');
            deploy_log('已上传 docker-compose.yaml', 'ok');
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

        $label = $service ? "({$service})" : '';
        deploy_log("=== 容器日志: {$projectName} {$label} ===", 'step');

        try {
            $this->ssh->connect();

            $composeFile = 'docker-compose.yaml';

            $svcArg = $service ? " {$service}" : '';
            $this->ssh->exec("cd {$projectPath} && " . get_compose_cmd() . " -f {$composeFile} logs --tail=50{$svcArg}");

        } catch (Exception $e) {
            deploy_log("获取日志失败: " . $e->getMessage(), 'error');
        }

        $this->ssh->disconnect();
    }
}
