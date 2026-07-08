<?php

/**
 * Router Nginx 配置管理
 * 
 * 支持两种模式：
 *   docker_router — Docker 容器 nginx（纯 Docker 环境，默认）
 *   host_nginx    — 宿主机已有 nginx（已有站点 + certbot）
 * 
 * init:router 时自动检测环境，无 -y 则只报告，有 -y 才执行。
 */
class RouterManager
{
    protected string $configDir;
    protected string $containerName;

    public const MODE_DOCKER = 'docker_router';
    public const MODE_HOST = 'host_nginx';

    /**
     * @param DeploySSH $ssh
     * @param array $routerConfig
     *  - containerName: Docker Router 容器名
     *  - configDir: 宿主机 nginx 配置目录（宿主机模式）或 Docker Router 配置目录
     *  - composePath: Docker Router 的 docker-compose.yaml 所在路径
     *  - mode: 手动指定模式（不指定则 auto 检测）
     */
    public function __construct(
        protected DeploySSH $ssh,
        protected array $routerConfig = []
    ) {
        $this->containerName = $routerConfig['containerName'] ?? 'phalcon-router';
        $this->configDir = $routerConfig['configDir'] ?? '/etc/nginx-router/conf.d';
    }

    /* ---------------- 环境检测 ---------------- */

    /**
     * 检测服务器环境，返回检测报告
     *
     * @return array [
     *   'os'        => string,
     *   'nginx'     => ['installed' => bool, 'running' => bool, 'configDir' => string],
     *   'certbot'   => ['installed' => bool],
     *   'port80'    => 'free'|'in_use',
     *   'port443'   => 'free'|'in_use',
     *   'dockerRouterRunning' => bool,
     *   'recommendedMode'     => 'docker_router'|'host_nginx',
     * ]
     */
    /**
     * 检测操作系统发行版本
     */
    protected function detectOS(): string
    {
        $result = $this->ssh->exec(
            ". /etc/os-release 2>/dev/null && echo \"\$PRETTY_NAME\" || echo 'unknown'",
            false
        );
        return trim($result) ?: 'unknown';
    }

    public function detect(): array
    {
        deploy_log('检测服务器环境...', 'info');

        // OS
        $os = $this->detectOS();

        // Nginx — 先检查进程，再检查命令
        $nginxRunning = $this->checkProcessRunning('nginx');
        $nginxInstalled = $nginxRunning || $this->checkInstalled('nginx');
        $nginxConfigDir = '/etc/nginx/conf.d';

        if ($nginxRunning) {
            $nginxConfigDir = $this->detectNginxConfigDir();
        }

        // Certbot
        $certbotInstalled = $this->checkInstalled('certbot');

        // 端口 80/443
        $port80 = $this->checkPort(80);
        $port443 = $this->checkPort(443);

        // Docker Router 容器是否已在运行
        $dockerRouterRunning = $this->checkDockerRouterRunning();

        // Docker 环境
        $dockerInstalled = $this->checkInstalled('docker');
        $dockerComposeCmd = $this->detectComposeCommand();
        $dockerComposeInstalled = !empty($dockerComposeCmd);
        $dockerNetworkExists = $dockerInstalled && $this->checkDockerNetworkExists();

        // 缓存 compose 命令名
        if ($dockerComposeInstalled) {
            $this->cacheComposeCommand($dockerComposeCmd);
        }

        // 推荐模式
        $recommendedMode = $this->determineMode(
            $nginxRunning || $nginxInstalled, $port80, $dockerRouterRunning
        );

        $report = [
            'os' => $os,
            'nginx' => [
                'installed' => $nginxInstalled,
                'running' => $nginxRunning,
                'configDir' => $nginxConfigDir,
            ],
            'certbot' => [
                'installed' => $certbotInstalled,
            ],
            'port80' => $port80,
            'port443' => $port443,
            'dockerRouterRunning' => $dockerRouterRunning,
            'docker' => [
                'installed' => $dockerInstalled,
                'composeInstalled' => $dockerComposeInstalled,
                'composeCmd' => $dockerComposeCmd,
                'networkExists' => $dockerNetworkExists,
            ],
            'recommendedMode' => $recommendedMode,
        ];

        $this->printDetectionReport($report);

        return $report;
    }

    /**
     * 打印检测报告
     */
    protected function printDetectionReport(array $report): void
    {
        echo "\n";
        deploy_log('━━━━━ 服务器环境检测报告 ━━━━━', 'step');
        deploy_log("系统:       {$report['os']}", 'info');
        deploy_log(
            sprintf("Nginx:      %s  %s",
                $report['nginx']['installed'] ? "\033[32m已安装\033[0m" : "\033[33m未安装\033[0m",
                $report['nginx']['running'] ? "\033[32m(运行中)\033[0m" : "\033[33m(未运行)\033[0m"
            ),
            'info'
        );
        if ($report['nginx']['installed']) {
            deploy_log("Nginx 配置: {$report['nginx']['configDir']}", 'info');
        }
        deploy_log(
            sprintf("Certbot:    %s",
                $report['certbot']['installed'] ? "\033[32m已安装\033[0m" : "\033[33m未安装\033[0m"
            ),
            'info'
        );
        deploy_log(
            sprintf("端口 80:    %s",
                $report['port80'] === 'in_use' ? "\033[33m已被占用\033[0m" : "\033[32m空闲\033[0m"
            ),
            'info'
        );
        deploy_log(
            sprintf("端口 443:   %s",
                $report['port443'] === 'in_use' ? "\033[33m已被占用\033[0m" : "\033[32m空闲\033[0m"
            ),
            'info'
        );
        deploy_log(
            sprintf("Docker Router: %s",
                $report['dockerRouterRunning'] ? "\033[32m已在运行\033[0m" : "\033[33m未运行\033[0m"
            ),
            'info'
        );
        deploy_log(
            sprintf("Docker:      %s",
                $report['docker']['installed'] ? "\033[32m已安装\033[0m" : "\033[33m未安装\033[0m"
            ),
            'info'
        );
        deploy_log(
            sprintf("Docker Compose: %s",
                $report['docker']['composeInstalled'] ? "\033[32m{$report['docker']['composeCmd']}\033[0m" : "\033[33m未安装\033[0m"
            ),
            'info'
        );
        deploy_log(
            sprintf("网络 phalcon-shared: %s",
                $report['docker']['networkExists'] ? "\033[32m已创建\033[0m" : "\033[33m未创建\033[0m"
            ),
            'info'
        );
        echo "\n";
        deploy_log(
            sprintf("推荐模式:   \033[36m%s\033[0m",
                $report['recommendedMode'] === self::MODE_HOST ? '宿主机 Nginx' : 'Docker Router'
            ),
            'step'
        );
        echo "\n";
    }

    /**
     * 获取检测结果中的推荐模式
     */
    public function getRecommendedMode(?array $detectResult = null): string
    {
        if ($detectResult === null) {
            $detectResult = $this->detect();
        }
        return $detectResult['recommendedMode'];
    }

    /* ---------------- 初始化 Router ---------------- */

    /**
     * 初始化 Router
     *
     * @param bool $autoExecute  true=执行安装, false=只检测报告
     * @return string|null 返回选定的模式，或 null（只检测不执行时）
     */
    public function initRouter(bool $autoExecute = false): ?string
    {
        $report = $this->detect();

        if (!$autoExecute) {
            deploy_log('使用 -y 参数执行安装：php deploy init:router -y', 'warn');
            deploy_log('或手动指定模式覆盖：php deploy init:router -y mode=host_nginx', 'warn');
            return null;
        }

        $mode = $this->routerConfig['mode'] ?? $report['recommendedMode'];

        if ($mode === self::MODE_DOCKER) {
            $this->setupDockerRouter($report);
        } else {
            $this->setupHostNginx($report);
        }

        // 不论哪种模式，都需要创建共享网络
        $this->ssh->exec("docker network create phalcon-shared 2>/dev/null || echo 'network already exists'", false);

        deploy_log("Router 初始化完成（模式: {$mode}）", 'ok');

        return $mode;
    }

    /**
     * 部署 Docker Router 容器
     */
    protected function setupDockerRouter(array $report): void
    {
        deploy_log('部署 Docker Router 容器...', 'step');

        $composePath = $this->routerConfig['composePath'] ?? '/root/router';
        $this->ssh->ensureDir($composePath);
        $this->ssh->ensureDir($this->configDir);

        $composeContent = $this->generateRouterCompose();
        $this->ssh->uploadContent($composeContent, $composePath . '/docker-compose.yaml');

        $this->ssh->exec("cd {$composePath} && " . get_compose_cmd() . " up -d");
    }

    /**
     * 配置宿主机 nginx（已有 nginx，不部署容器）
     */
    protected function setupHostNginx(array $report): void
    {
        deploy_log('配置宿主机 Nginx...', 'step');

        $targetDir = $report['nginx']['configDir'] ?? '/etc/nginx/conf.d';

        // 创建存放工具生成配置的目录
        $this->ssh->exec("mkdir -p {$targetDir}", false);

        // 更新 configDir 指向宿主机 nginx 目录，后续 addDomain 直接写入
        $this->configDir = $targetDir;

        // 如果 certbot 未安装，给出提示
        if (!$report['certbot']['installed']) {
            deploy_log('提示: certbot 未安装，SSL 证书需手动配置', 'warn');
            deploy_log('  安装: apt install certbot python3-certbot-nginx', 'info');
        }

        // 检查 nginx 配置是否有效
        $this->ssh->exec("nginx -t 2>&1", false);
    }

    /* ---------------- 域名管理（自动适配模式） ---------------- */

    /**
     * 为项目添加域名转发规则
     * 自动根据当前模式决定 target（Docker DNS 或 127.0.0.1:端口）
     *
     * @param string $projectName
     * @param array $domains
     * @param bool $ssl
     * @param string|null $mode  'docker_router' | 'host_nginx'
     * @param int|null $nginxPort  宿主机模式时项目的 nginx 端口
     */
    public function addDomain(string $projectName, array $domains, bool $ssl = false, ?string $mode = null, ?int $nginxPort = null): void
    {
        if (empty($domains)) {
            deploy_log('无域名配置，跳过 Router 更新', 'warn');
            return;
        }

        if ($mode === null) {
            $mode = $this->detectCachedMode();
        }

        // 根据模式设置配置目录
        if ($mode === self::MODE_HOST && $this->configDir !== '/etc/nginx/conf.d') {
            $this->configDir = '/etc/nginx/conf.d';
        }

        // 决定转发的目标地址
        if ($mode === self::MODE_DOCKER) {
            $target = $projectName . '-nginx:80';
        } else {
            // 宿主机模式：需要端口，缺省 8071
            if (empty($nginxPort)) {
                $nginxPort = 8071;
            }
            $target = '127.0.0.1:' . $nginxPort;
        }

        deploy_log("添加域名: " . implode(', ', $domains) . " → {$target}（模式: {$mode}）", 'step');

        $configContent = $this->generateServerBlock($domains, $target, $ssl);

        if ($mode === self::MODE_HOST) {
            // 宿主机模式：写入宿主机 nginx 目录
            $remoteFile = $this->configDir . '/' . $projectName . '.conf';
        } else {
            // Docker Router 模式：写入 Router 配置目录
            $remoteFile = $this->configDir . '/' . $projectName . '.conf';
        }

        $this->ssh->exec("mkdir -p " . dirname($remoteFile), false);
        $this->ssh->uploadContent($configContent, $remoteFile);
        deploy_log("配置已上传: {$remoteFile}", 'ok');

        $this->reload();
    }

    /**
     * 从 Router 移除项目的域名配置
     */
    public function removeDomain(string $projectName): void
    {
        $remoteFile = $this->configDir . '/' . $projectName . '.conf';
        $exists = $this->ssh->exec("[ -f {$remoteFile} ] && echo 'YES' || echo 'NO'", false);

        if (trim($exists) === 'YES') {
            $this->ssh->exec("rm {$remoteFile}");
            deploy_log("已移除配置: {$remoteFile}", 'ok');
            $this->reload();
        } else {
            deploy_log("配置不存在: {$remoteFile}", 'warn');
        }
    }

    /* ---------------- 内部方法 ---------------- */

    /**
     * 检测命令是否已安装
     */
    protected function checkInstalled(string $cmd): bool
    {
        // 重定向所有输出到 /dev/null，只输出标记
        $result = $this->ssh->exec(
            "command -v {$cmd} >/dev/null 2>&1 && echo 'YES' || echo 'NO'",
            false
        );
        return trim($result) === 'YES';
    }

    /**
     * 检测进程是否在运行
     * 使用 ps aux，不依赖 PATH（pidof/pgrep 在 ssh 非交互 shell 中可能不可用）
     */
    protected function checkProcessRunning(string $name): bool
    {
        $result = $this->ssh->exec(
            "ps aux 2>/dev/null | grep -v grep | grep -q ' {$name}' && echo 'YES' || echo 'NO'",
            false
        );
        return trim($result) === 'YES';
    }

    /**
     * 检测端口是否被占用
     * 逐级尝试 ss → netstat，不加 -p 避免权限问题
     */
    protected function checkPort(int $port): string
    {
        $result = $this->ssh->exec(
            "ss -tln 2>/dev/null | grep -q '\\.{$port} ' && echo 'in_use' || " .
            "netstat -tln 2>/dev/null | grep -q '\\.{$port} ' && echo 'in_use' || echo 'free'",
            false
        );
        $trimmed = trim($result);
        return $trimmed === 'in_use' ? $trimmed : 'free';
    }

    /**
     * 检测 Docker Router 容器是否已在运行
     */
    protected function checkDockerRouterRunning(): bool
    {
        $result = $this->ssh->exec(
            "docker inspect -f '{{.State.Running}}' {$this->containerName} 2>/dev/null || echo 'false'",
            false
        );
        return trim($result) === 'true';
    }

    /**
     * 检测 phalcon-shared Docker 网络是否已创建
     */
    protected function checkDockerNetworkExists(): bool
    {
        $result = $this->ssh->exec(
            "docker network inspect phalcon-shared >/dev/null 2>&1 && echo 'YES' || echo 'NO'",
            false
        );
        return trim($result) === 'YES';
    }

    /**
     * 检测可用的 Docker Compose 命令（v2 docker compose / v1 docker-compose）
     */
    protected function detectComposeCommand(): string
    {
        // 先检测 docker compose（v2）
        $result = $this->ssh->exec(
            "docker compose version >/dev/null 2>&1 && echo 'docker compose' || echo ''",
            false
        );
        $cmd = trim($result);
        if (!empty($cmd)) {
            return $cmd;
        }

        // 再检测 docker-compose（v1）
        $result = $this->ssh->exec(
            "docker-compose --version >/dev/null 2>&1 && echo 'docker-compose' || echo ''",
            false
        );
        return trim($result);
    }

    /**
     * 缓存 compose 命令名到本地
     */
    protected function cacheComposeCommand(string $cmd): void
    {
        $cacheDir = deploy_base_path() . '/.cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        file_put_contents($cacheDir . '/compose-cmd.txt', $cmd);
        deploy_log("Compose 命令已缓存: {$cmd}", 'ok');
    }

    /**
     * 检测宿主机 nginx 配置目录
     */
    protected function detectNginxConfigDir(): string
    {
        // nginx 站点配置目录，CentOS/Ubuntu/Debian 均默认为 /etc/nginx/conf.d
        return '/etc/nginx/conf.d';
    }

    /**
     * 决定推荐模式
     */
    protected function determineMode(bool $nginxRunning, string $port80, bool $dockerRouterRunning): string
    {
        if ($dockerRouterRunning) {
            return self::MODE_DOCKER;
        }
        // 宿主机 nginx 在运行 或 80 端口已被占用 → 推荐宿主机模式
        if ($nginxRunning || $port80 === 'in_use') {
            return self::MODE_HOST;
        }
        // 全新服务器 → Docker Router
        return self::MODE_DOCKER;
    }

    /**
     * 检测缓存的模式（尝试从服务器上已有的 Router 容器/nginx 配置判断）
     */
    protected function detectCachedMode(): string
    {
        // 先看是否有 Docker Router 容器在运行
        if ($this->checkDockerRouterRunning()) {
            return self::MODE_DOCKER;
        }
        // 再看宿主机 nginx 是否有我们生成的配置
        $result = $this->ssh->exec(
            "[ -f {$this->configDir}/.deploy-mode ] && cat {$this->configDir}/.deploy-mode || echo ''",
            false
        );
        $mode = trim($result);
        if ($mode === self::MODE_DOCKER || $mode === self::MODE_HOST) {
            return $mode;
        }
        // 回退：自动检测
        $report = $this->detect();
        return $report['recommendedMode'];
    }

    /**
     * 重载 Nginx
     */
    public function reload(): void
    {
        deploy_log("重载 Nginx", 'step');

        $output = $this->ssh->exec(
            "docker exec {$this->containerName} nginx -s reload 2>/dev/null || " .
            "nginx -s reload 2>/dev/null || " .
            "systemctl reload nginx 2>/dev/null || " .
            "echo 'RELOAD_FAILED'",
            false
        );

        if (trim($output) === 'RELOAD_FAILED') {
            deploy_log('Nginx 重载失败，请手动执行重载', 'warn');
        } else {
            deploy_log('Nginx 已重载', 'ok');
        }
    }

    /**
     * 验证 Nginx 配置语法后重载
     */
    public function validateAndReload(): void
    {
        deploy_log('验证 Nginx 配置语法', 'step');

        $this->ssh->exec(
            "docker exec {$this->containerName} nginx -t 2>/dev/null || " .
            "nginx -t",
            true
        );

        deploy_log('Nginx 配置语法正确', 'ok');
        $this->reload();
    }

    /**
     * 查看/下载 Nginx 日志
     */
    public function nginxLog(string $type, bool $download = false): void
    {
        $label = $type === 'error' ? '错误' : '访问';
        $logFile = "/var/log/nginx/{$type}.log";

        // 先尝试 Docker Router 容器，失败则回退到宿主机路径
        $isDocker = $this->checkDockerRouterRunning();

        if ($download) {
            // 下载到本地
            if ($isDocker) {
                $tmpPath = "/tmp/nginx-{$type}-{$this->containerName}.log";
                $this->ssh->exec("docker cp {$this->containerName}:{$logFile} {$tmpPath} 2>/dev/null || echo 'CP_FAILED'", false);
            }
            $localPath = deploy_base_path() . "/logs/nginx-{$type}-" . date('YmdHis') . '.log';
            $logDir = dirname($localPath);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            $srcPath = $isDocker ? $tmpPath : $logFile;
            $this->ssh->download($srcPath, $localPath);
            if ($isDocker) {
                $this->ssh->exec("rm -f {$tmpPath}", false);
            }
            deploy_log("日志已保存: {$localPath}", 'ok');
        } else {
            // 远程 tail 查看
            deploy_log("=== Nginx {$label}日志 (100行) ===", 'step');
            $cmd = $isDocker
                ? "docker exec {$this->containerName} tail -n 100 {$logFile} 2>/dev/null || echo '日志文件不存在'"
                : "tail -n 100 {$logFile} 2>/dev/null || echo '日志文件不存在'";
            $this->ssh->exec($cmd);
        }
    }

    /**
     * 生成 nginx server block 配置
     */
    protected function generateServerBlock(array $domains, string $target, bool $ssl = false): string
    {
        $serverName = implode(' ', $domains);
        $primaryDomain = $domains[0];

        $config = <<<NGINX
# Auto-generated by deploy tool for: {$serverName}
server {
    listen 80;
    server_name {$serverName};

    proxy_http_version 1.1;
    proxy_read_timeout 120;

    proxy_set_header Host \$http_host;
    proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
    proxy_set_header X-Real-IP \$remote_addr;
    proxy_set_header X-Forwarded-Host \$host;
    proxy_set_header X-Forwarded-Proto \$scheme;
    proxy_set_header X-Forwarded-Port \$server_port;

    proxy_set_header Upgrade \$http_upgrade;
    proxy_set_header Connection \$http_upgrade;

    location / {
        proxy_pass http://{$target};
    }
}

NGINX;

        if ($ssl) {
            $config .= <<<NGINX
server {
    listen 443 ssl http2;
    server_name {$serverName};

    ssl_certificate     /etc/nginx/ssl/{$primaryDomain}.pem;
    ssl_certificate_key /etc/nginx/ssl/{$primaryDomain}.key;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_ciphers         HIGH:!aNULL:!MD5;

    proxy_http_version 1.1;
    proxy_read_timeout 120;

    proxy_set_header Host \$http_host;
    proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
    proxy_set_header X-Real-IP \$remote_addr;
    proxy_set_header X-Forwarded-Host \$host;
    proxy_set_header X-Forwarded-Proto \$scheme;
    proxy_set_header X-Forwarded-Port \$server_port;

    proxy_set_header Upgrade \$http_upgrade;
    proxy_set_header Connection \$http_upgrade;

    location / {
        proxy_pass http://{$target};
    }
}

NGINX;
        }

        return $config;
    }

    /**
     * 生成 Docker Router 的 docker-compose.yaml
     */
    protected function generateRouterCompose(): string
    {
        return <<<YAML
version: '3.5'

services:
  router:
    image: nginx:stable-alpine
    container_name: {$this->containerName}
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - {$this->configDir}:/etc/nginx/conf.d
      - /etc/nginx-router/ssl:/etc/nginx/ssl
    networks:
      - phalcon-shared
    restart: always

networks:
  phalcon-shared:
    external: true
YAML;
    }
}
