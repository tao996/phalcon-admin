<?php

/**
 * 数据库运维管理
 *
 * 功能：
 * - db:proxy  — SSH 隧道转发（本地端口 → 远程项目的 MySQL）
 * - db:pma    — 临时部署 phpMyAdmin 容器，用完即删
 * - db:pma-rm — 清理临时 phpMyAdmin
 */
class DbManager
{
    protected DeploySSH $ssh;
    protected DeployConfig $config;

    public function __construct(DeployConfig $config)
    {
        $this->config = $config;
        $this->ssh = new DeploySSH($config->getSshConfig());
    }

    /* ---------------- 方案 A：SSH 隧道 ---------------- */

    /**
     * 建立 SSH 隧道，将远程项目的 MySQL 端口转发到本地
     *
     * 原理：调用系统 ssh -L 命令，在本地监听一个端口，
     * 所有发往该端口的流量经 SSH 加密隧道转发到远程的 MySQL 容器。
     *
     * @param string $projectName  项目名（同服务器上 docker-compose 的项目名）
     * @param int $localPort       本地监听端口（默认 13306）
     */
    public function proxyTunnel(string $projectName, int $localPort = 13306): void
    {
        $sshConfig = $this->config->getSshConfig();
        $host = $sshConfig['host'] ?? '';
        $port = $sshConfig['port'] ?? 22;
        $user = $sshConfig['user'] ?? 'root';

        if (empty($host)) {
            deploy_log('未配置服务器 host', 'error');
            exit(1);
        }

        $mysqlHost = $projectName . '-mysql';
        $localBind = "127.0.0.1:{$localPort}";
        $remoteTarget = "{$mysqlHost}:3306";

        echo "\n";
        deploy_log('━━━━━ SSH 隧道 ━━━━━', 'step');
        deploy_log("本地:     {$localBind}", 'info');
        deploy_log("远程:     {$remoteTarget}", 'info');
        deploy_log("服务器:   {$user}@{$host}:{$port}", 'info');
        echo "\n";
        deploy_log('连接命令:', 'info');
        deploy_log("  mysql -h127.0.0.1 -P{$localPort} -u<用户名> -p", 'cmd');
        echo "\n";
        deploy_log('按 Ctrl+C 关闭隧道', 'warn');
        echo "\n";

        // 构建系统 ssh 命令
        $sshArgs = [
            '-L', "{$localBind}:{$remoteTarget}",
            '-N',                           // 不执行远程命令，只转发
            '-o', 'ExitOnForwardFailure=yes',
            '-o', 'ServerAliveInterval=30', // 每 30s 发心跳保持连接
        ];

        // 指定私钥（如果配置了）
        if (!empty($sshConfig['keyFile'])) {
            $keyPath = $sshConfig['keyFile'];
            if (str_starts_with($keyPath, '~')) {
                $home = getenv('HOME') ?: (getenv('USERPROFILE') ?: '');
                $keyPath = $home . substr($keyPath, 1);
            }
            if (file_exists($keyPath)) {
                $sshArgs[] = '-i';
                $sshArgs[] = $keyPath;
            }
        }

        $sshArgs[] = '-p';
        $sshArgs[] = $port;
        $sshArgs[] = "{$user}@{$host}";

        // 查找 SSH 二进制
        $sshBinary = $this->findSshBinary();
        if ($sshBinary === null) {
            deploy_log('错误: 未找到 ssh 命令（PATH 中无可用的 ssh）', 'error');
            deploy_log('提示: 也可手动执行以下命令：', 'info');
            $manualCmd = "ssh -L {$localBind}:{$remoteTarget} -N {$user}@{$host} -p {$port}";
            deploy_log("  {$manualCmd}", 'cmd');
            exit(1);
        }

        $cmd = $sshBinary . ' ' . implode(' ', array_map('escapeshellarg', $sshArgs));
        deploy_log("执行: ssh -L {$localBind}:{$remoteTarget} -N {$user}@{$host} -p {$port}", 'cmd');

        // passthru 会阻塞，直到用户 Ctrl+C
        // SSH 隧道本身在前台运行，用户按 Ctrl+C 终止
        passthru($cmd, $exitCode);

        if ($exitCode !== 0 && $exitCode !== 130) { // 130 = Ctrl+C
            deploy_log("隧道异常退出（代码: {$exitCode}）", 'warn');
        } else {
            deploy_log('隧道已关闭', 'ok');
        }
    }

    /* ---------------- 方案 D：临时 phpMyAdmin ---------------- */

    /**
     * 在远程服务器上部署一个临时的 phpMyAdmin 容器
     *
     * 容器连接到项目的 Docker 网络，通过 host 端口暴露，
     * 用户通过 http://服务器IP:端口 访问。
     * 使用完后通过 db:pma-rm 清理。
     *
     * @param string $projectName  项目名
     * @param int $hostPort        宿主机端口（默认 13307）
     */
    public function deployPhpMyAdmin(string $projectName, int $hostPort = 13307, string $dbUser = 'admin', string $dbPassword = ''): void
    {
        $this->ssh->connect();

        $containerName = $projectName . '-pma';

        // 检查是否已有同名容器
        $existing = $this->ssh->exec(
            "docker inspect -f '{{.State.Running}}' {$containerName} 2>/dev/null || echo 'not_found'",
            false
        );
        if (trim($existing) === 'true') {
            deploy_log("phpMyAdmin 已在运行: {$containerName}", 'warn');
            $this->printPmaUrl($projectName, $hostPort, $dbUser, $dbPassword);
            $this->ssh->disconnect();
            return;
        }

        // 获取项目的 Docker 网络名
        // docker-compose 创建的网络名为: {compose_dir}_{network_name}
        // 我们的模板中 network_name = backend
        // 所以实际网络名通常是 {project_name}_backend
        $networkName = $projectName . '_backend';

        deploy_log("部署 phpMyAdmin 容器: {$containerName}", 'step');
        deploy_log("网络: {$networkName}", 'info');
        deploy_log("端口: {$hostPort}", 'info');

        // 拉取并启动 phpMyAdmin 容器
        $cmd = sprintf(
            'docker run -d --rm --name %s --network %s -p %d:80 -e PMA_HOST=mysql -e PMA_PORT=3306 phpmyadmin/phpmyadmin',
            escapeshellarg($containerName),
            escapeshellarg($networkName),
            $hostPort
        );

        $output = $this->ssh->exec($cmd);
        $containerId = trim($output);

        if (empty($containerId)) {
            deploy_log('phpMyAdmin 启动失败', 'error');

            // 尝试查找可用的网络
            deploy_log('尝试查找项目的 Docker 网络...', 'info');
            $networks = $this->ssh->exec(
                "docker network ls --filter name={$projectName} --format '{{.Name}}' 2>/dev/null",
                false
            );
            if (!empty(trim($networks))) {
                deploy_log("可用网络: " . str_replace("\n", ', ', trim($networks)), 'info');
            } else {
                deploy_log("未找到包含 '{$projectName}' 的 Docker 网络", 'warn');
                deploy_log("请确认项目已执行过 docker-compose up", 'info');
            }

            $this->ssh->disconnect();
            exit(1);
        }

        deploy_log("phpMyAdmin 已启动", 'ok');
        $this->printPmaUrl($projectName, $hostPort, $dbUser, $dbPassword);
        deploy_log("清理命令: php deploy db:pma-rm {$projectName}", 'info');

        $this->ssh->disconnect();
    }

    /**
     * 删除临时 phpMyAdmin 容器
     */
    public function removePhpMyAdmin(string $projectName): void
    {
        $this->ssh->connect();

        $containerName = $projectName . '-pma';

        $exists = $this->ssh->exec(
            "docker inspect -f '{{.Id}}' {$containerName} 2>/dev/null || echo 'not_found'",
            false
        );

        if (trim($exists) === 'not_found') {
            deploy_log("容器不存在: {$containerName}", 'warn');
            $this->ssh->disconnect();
            return;
        }

        deploy_log("删除容器: {$containerName}", 'step');
        $this->ssh->exec("docker rm -f {$containerName}");

        deploy_log("phpMyAdmin 已清理", 'ok');

        $this->ssh->disconnect();
    }

    /* ---------------- 内部方法 ---------------- */

    /**
     * 打印 phpMyAdmin 访问地址
     */
    protected function printPmaUrl(string $projectName, int $hostPort, string $dbUser = '', string $dbPassword = ''): void
    {
        $sshConfig = $this->config->getSshConfig();
        $serverIp = $sshConfig['host'] ?? '服务器IP';

        echo "\n";
        deploy_log('━━━━━ phpMyAdmin 访问地址 ━━━━━', 'step');
        deploy_log("  http://{$serverIp}:{$hostPort}", 'info');
        if ($dbUser && $dbPassword) {
            deploy_log("  用户名: {$dbUser}", 'info');
            deploy_log("  密码:   {$dbPassword}", 'info');
        } else {
            deploy_log("  用户名: 从项目 server.php 的 env.MYSQL_USER 获取", 'info');
            deploy_log("  密码:   从项目 server.php 的 env.MYSQL_PASSWORD 获取", 'info');
        }
        echo "\n";
    }

    /**
     * 查找系统中可用的 SSH 二进制文件
     */
    protected function findSshBinary(): ?string
    {
        // 常见 SSH 路径
        $candidates = [
            'C:\Program Files\Git\usr\bin\ssh.exe',   // Git Bash
            'C:\Windows\System32\OpenSSH\ssh.exe',    // Windows OpenSSH
            '/usr/bin/ssh',
            '/usr/local/bin/ssh',
        ];

        // 先尝试 PATH 中的 ssh
        $pathSsh = trim(shell_exec('where ssh 2>nul || which ssh 2>/dev/null'));
        if (!empty($pathSsh)) {
            $paths = explode("\n", $pathSsh);
            $first = trim($paths[0]);
            if (!empty($first) && file_exists($first)) {
                return $first;
            }
        }

        // fallback 到候选路径
        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
