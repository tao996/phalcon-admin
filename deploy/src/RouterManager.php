<?php

/**
 * 宿主机 Nginx 配置管理
 *
 * server:init 时检测服务器环境（nginx/certbot/docker compose），
 * app:init / nginx:add 时为项目生成 server block 写入 /etc/nginx/conf.d 并重载 nginx。
 *
 * 项目容器 nginx 绑定 127.0.0.1:<nginxPort>，由宿主机 nginx 反向代理对外服务。
 */
class RouterManager
{
    protected string $configDir = '/etc/nginx/conf.d';

    public function __construct(protected DeploySSH $ssh)
    {
    }

    /* ---------------- 环境检测 ---------------- */

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

        // Certbot
        $certbotInstalled = $this->checkInstalled('certbot');

        // 端口 80/443
        $port80 = $this->checkPort(80);
        $port443 = $this->checkPort(443);

        // Docker 环境
        $dockerInstalled = $this->checkInstalled('docker');
        $dockerComposeCmd = $this->detectComposeCommand();
        $dockerComposeInstalled = !empty($dockerComposeCmd);

        // 缓存 compose 命令名
        if ($dockerComposeInstalled) {
            $this->cacheComposeCommand($dockerComposeCmd);
        }

        $report = [
            'os' => $os,
            'nginx' => [
                'installed' => $nginxInstalled,
                'running' => $nginxRunning,
                'configDir' => $this->configDir,
            ],
            'certbot' => [
                'installed' => $certbotInstalled,
            ],
            'port80' => $port80,
            'port443' => $port443,
            'docker' => [
                'installed' => $dockerInstalled,
                'composeInstalled' => $dockerComposeInstalled,
                'composeCmd' => $dockerComposeCmd,
            ],
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
        echo "\n";
    }

    /* ---------------- 初始化 ---------------- */

    /**
     * 服务器初始化：检测环境；-y 时确认 nginx 配置目录可用
     */
    public function initRouter(bool $autoExecute = false): void
    {
        $report = $this->detect();

        if (!$autoExecute) {
            deploy_log('使用 -y 参数执行安装：php admin server:init -y', 'warn');
            return;
        }

        deploy_log('配置宿主机 Nginx...', 'step');

        // 创建存放工具生成配置的目录
        $this->ssh->exec("mkdir -p {$this->configDir}", false);

        // 如果 certbot 未安装，给出提示
        if (!$report['certbot']['installed']) {
            deploy_log('提示: certbot 未安装，SSL 证书需手动配置', 'warn');
            deploy_log('  安装: apt install certbot python3-certbot-nginx', 'info');
        }

        // 检查 nginx 配置是否有效
        $this->ssh->exec("nginx -t 2>&1", false);

        deploy_log('服务器初始化完成', 'ok');
    }

    /* ---------------- 域名管理 ---------------- */

    /**
     * 为项目添加域名转发规则（写入 /etc/nginx/conf.d/<项目>.conf）
     *
     * @param string $projectName
     * @param array $domains
     * @param bool $ssl
     * @param int|null $nginxPort 项目的 nginx 端口（缺省 8071）
     */
    public function addDomain(string $projectName, array $domains, bool $ssl = false, ?int $nginxPort = null): void
    {
        if (empty($domains)) {
            deploy_log('无域名配置，跳过', 'warn');
            return;
        }

        $target = '127.0.0.1:' . ($nginxPort ?: 8071);
        deploy_log("添加域名: " . implode(', ', $domains) . " → {$target}", 'step');

        $configContent = $this->generateServerBlock($domains, $target, $ssl);
        $remoteFile = $this->configDir . '/' . $projectName . '.conf';

        $this->ssh->exec("mkdir -p " . dirname($remoteFile), false);
        $this->ssh->uploadContent($configContent, $remoteFile);
        deploy_log("配置已上传: {$remoteFile}", 'ok');

        $this->reload();
    }

    /**
     * 移除项目的域名配置
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
        set_server_cache(['composeCmd' => $cmd]);
        deploy_log("Compose 命令已缓存: {$cmd}", 'ok');
    }

    /**
     * 重载 Nginx
     */
    public function reload(): void
    {
        deploy_log("重载 Nginx", 'step');

        $output = $this->ssh->exec(
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

        $this->ssh->exec("nginx -t", true);

        deploy_log('Nginx 配置语法正确', 'ok');
        $this->reload();
    }

    /**
     * 查看/下载 Nginx 日志（宿主机 /var/log/nginx）
     */
    public function nginxLog(string $type, bool $download = false): void
    {
        $label = $type === 'error' ? '错误' : '访问';
        $logFile = "/var/log/nginx/{$type}.log";

        if ($download) {
            $localPath = deploy_base_path() . "/logs/nginx-{$type}-" . date('YmdHis') . '.log';
            $logDir = dirname($localPath);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            $this->ssh->download($logFile, $localPath);
            deploy_log("日志已保存: {$localPath}", 'ok');
        } else {
            deploy_log("=== Nginx {$label}日志 (100行) ===", 'step');
            $this->ssh->exec("tail -n 100 {$logFile} 2>/dev/null || echo '日志文件不存在'");
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
     * 为域名申请 SSL 证书并启用 HTTPS
     */
    public function enableSSL(string $projectName, array $domains, string $projectPath, string $email, ?int $nginxPort = null): void
    {
        $primaryDomain = $domains[0] ?? '';
        if (empty($primaryDomain)) {
            deploy_log('未配置域名，跳过 SSL', 'warn');
            return;
        }

        // 检查 certbot 是否安装
        $certbotInstalled = $this->checkInstalled('certbot');
        if (!$certbotInstalled) {
            deploy_log('certbot 未安装，请先安装: apt install certbot', 'error');
            return;
        }

        deploy_log("=== 申请 SSL 证书: {$primaryDomain} ===", 'step');

        // 1. 创建证书目录
        $this->ssh->exec('mkdir -p /etc/nginx/ssl', false);

        // 2. 申请证书
        $webroot = $projectPath . '/src/public';
        $domainArgs = '';
        foreach ($domains as $d) {
            $domainArgs .= ' -d ' . escapeshellarg($d);
        }
        $this->ssh->exec(
            "certbot certonly --webroot -w {$webroot}{$domainArgs} --non-interactive --agree-tos -m " . escapeshellarg($email)
        );

        // 3. 创建软链到 /etc/nginx/ssl/
        $this->ssh->exec(
            "ln -sf /etc/letsencrypt/live/{$primaryDomain}/fullchain.pem /etc/nginx/ssl/{$primaryDomain}.pem && " .
            "ln -sf /etc/letsencrypt/live/{$primaryDomain}/privkey.pem /etc/nginx/ssl/{$primaryDomain}.key"
        );

        // 4. 重新生成含 SSL 的 server block
        $target = '127.0.0.1:' . ($nginxPort ?: 8071);
        $configContent = $this->generateServerBlock($domains, $target, true);
        $remoteFile = $this->configDir . '/' . $projectName . '.conf';
        $this->ssh->exec('mkdir -p ' . dirname($remoteFile), false);
        $this->ssh->uploadContent($configContent, $remoteFile);
        deploy_log("配置已上传: {$remoteFile}", 'ok');

        $this->reload();

        deploy_log("=== SSL 配置完成: {$primaryDomain} ===", 'ok');
    }
}
