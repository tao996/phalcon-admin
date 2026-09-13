<?php

/**
 * 数据库运维管理
 *
 * 功能：
 * - db:proxy       — SSH 隧道转发（本地端口 → 远程项目的 MySQL）
 * - db:pma         — 临时部署 phpMyAdmin 容器，用完即删
 * - db:pma-rm      — 清理临时 phpMyAdmin
 * - db:backup      — 立即备份数据库（mysqldump | gzip，存服务器，可选下载本地）
 * - db:backup:list — 查看服务器备份列表
 * - db:backup:get  — 下载备份到本地
 * - db:backup:cron — 安装/更新每日定时备份（服务器 crontab，带标记块幂等更新）
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

        // 探测项目的 Docker 网络名：compose 网络名 = 部署目录名_网络键（模板网络键为 backend），
        // 部署目录 basename 不一定等于项目名（如项目 boyu 部署在 /data/phalcon-admin），
        // 从 mysql 容器反查实际网络名最可靠，探测不到时回退到 <项目名>_backend 并告警
        $networkName = trim($this->ssh->exec(
            sprintf(
                "docker inspect %s --format '{{range \$k, \$_ := .NetworkSettings.Networks}}{{\$k}}{{end}}' 2>/dev/null",
                escapeshellarg($projectName . '-mysql')
            ),
            false
        ));
        if ($networkName === '') {
            $networkName = $projectName . '_backend';
            deploy_log("无法从 {$projectName}-mysql 容器反查网络名，回退为 {$networkName}", 'warn');
        }

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

        $this->ssh->exec($cmd);

        // docker run 的输出混有容器 ID 与错误信息，以实际运行状态为准
        $running = trim($this->ssh->exec(
            sprintf(
                "docker inspect -f '{{.State.Running}}' %s 2>/dev/null || echo 'not_found'",
                escapeshellarg($containerName)
            ),
            false
        ));

        if ($running !== 'true') {
            deploy_log('phpMyAdmin 启动失败（容器未在运行，--rm 已自动清理）', 'error');

            deploy_log('服务器上的 Docker 网络:', 'info');
            $networks = $this->ssh->exec(
                "docker network ls --format '{{.Name}}' 2>/dev/null",
                false
            );
            if (!empty(trim($networks))) {
                deploy_log('  ' . str_replace("\n", ', ', trim($networks)), 'info');
            }
            deploy_log("排查: docker ps -a 确认 {$projectName}-mysql 在运行", 'info');

            $this->ssh->disconnect();
            exit(1);
        }

        deploy_log("phpMyAdmin 已启动", 'ok');
        $this->printPmaUrl($projectName, $hostPort, $dbUser, $dbPassword);
        deploy_log("清理命令: php admin app:{$projectName} db:pma-rm", 'info');

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

    /* ---------------- 数据库备份 ---------------- */

    /**
     * 备份上下文：容器名 / 备份目录 / 库名
     *
     * 凭据不经过命令行：mysqldump 在容器内通过自身环境变量
     * （$MYSQL_USER/$MYSQL_PASSWORD/$MYSQL_DATABASE）取值。
     */
    protected function backupContext(): array
    {
        if ($this->config->getSyncTarget() === 'filesystem') {
            deploy_log('filesystem 目标项目没有远程 MySQL，不支持备份', 'error');
            exit(1);
        }

        $projectName = $this->config->getProjectName();
        $projectPath = $this->config->getProjectPath();
        $env = $this->config->getMerged()['env'] ?? [];
        $dbName = trim((string)($env['MYSQL_DATABASE'] ?? ''));
        if ($dbName === '') {
            deploy_log('项目 env 未配置 MYSQL_DATABASE', 'error');
            exit(1);
        }

        return [
            'project' => $projectName,
            'container' => $projectName . '-mysql',
            'dir' => rtrim($projectPath, '/') . '/docker/storage/backup/mysql',
            'db' => $dbName,
        ];
    }

    /**
     * mysqldump 管道命令（stdout → 宿主机 gzip → $redirect）
     *
     * @param string $redirect 已按远程 shell 语义构造好的重定向目标；
     *                         cron 场景文件名含 $(date ...)，需调用方保证引号拼接可展开
     */
    protected function dumpCmd(array $ctx, string $redirect): string
    {
        $dump = "docker exec {$ctx['container']} sh -c"
            . " 'exec mysqldump --single-transaction --routines --triggers --events"
            . " -u\"\$MYSQL_USER\" -p\"\$MYSQL_PASSWORD\" \"\$MYSQL_DATABASE\"'";
        return "{$dump} | gzip > {$redirect}";
    }

    /**
     * 立即备份：生成 <项目>_<库>_<时间>.sql.gz 到服务器备份目录
     *
     * @param bool $download 同时下载到本地 deploy/backups/<项目>/
     */
    public function backup(bool $download = false): void
    {
        $ctx = $this->backupContext();
        $file = safe_name($ctx['project']) . '_' . safe_name($ctx['db']) . '_' . date('Ymd_Hi') . '.sql.gz';
        $remoteFile = $ctx['dir'] . '/' . $file;

        deploy_log("=== 数据库备份: {$ctx['project']}（{$ctx['db']}） ===", 'step');

        $this->ssh->connect();
        try {
            $this->ssh->exec("mkdir -p " . $this->rq($ctx['dir']), false);
            $this->ssh->exec($this->dumpCmd($ctx, $this->rq($remoteFile)));

            $size = (int)trim($this->ssh->exec(
                "[ -s " . $this->rq($remoteFile) . " ] && stat -c %s " . $this->rq($remoteFile) . " || echo 0",
                false
            ));
            if ($size <= 0) {
                deploy_log("备份失败: 产物为空或不存在（{$remoteFile}）", 'error');
                exit(1);
            }
            deploy_log("备份完成: {$file}（" . round($size / 1024, 1) . " KiB）→ {$ctx['dir']}", 'ok');

            if ($download) {
                $this->downloadFile($ctx, $file, $remoteFile);
            }
        } finally {
            $this->ssh->disconnect();
        }
    }

    /**
     * 查看服务器备份列表
     */
    public function listBackups(): void
    {
        $ctx = $this->backupContext();

        $this->ssh->connect();
        try {
            deploy_log("=== 备份列表: {$ctx['dir']} ===", 'step');
            $this->ssh->exec(
                "ls -lh --time-style=long-iso " . $this->rq($ctx['dir'])
                . " 2>/dev/null | grep -v '^total' || echo '暂无备份'"
            );
        } finally {
            $this->ssh->disconnect();
        }
    }

    /**
     * 下载备份到本地 deploy/backups/<项目>/（缺省最新一份）
     */
    public function downloadBackup(string $file = ''): void
    {
        $ctx = $this->backupContext();

        $this->ssh->connect();
        try {
            if ($file === '') {
                $latest = trim($this->ssh->exec(
                    "ls -t " . $this->rq($ctx['dir']) . "/*.sql.gz 2>/dev/null | head -1",
                    false
                ));
                if ($latest === '') {
                    deploy_log('服务器上暂无备份', 'warn');
                    return;
                }
                $file = basename($latest);
                deploy_log("未指定文件，选择最新备份: {$file}", 'info');
            }

            $remoteFile = $ctx['dir'] . '/' . $file;
            $exists = trim($this->ssh->exec("[ -f " . $this->rq($remoteFile) . " ] && echo YES || echo NO", false));
            if ($exists !== 'YES') {
                deploy_log("备份文件不存在: {$file}（可用 db:backup:list 查看）", 'error');
                exit(1);
            }

            $this->downloadFile($ctx, $file, $remoteFile);
        } finally {
            $this->ssh->disconnect();
        }
    }

    /**
     * 安装/更新每日定时备份（服务器 crontab，标记块幂等更新）
     *
     * @param bool   $autoExecute false 时仅预览生成的 crontab 条目
     * @param string $time        每日执行时间，如 03:00
     * @param int    $keepDays    备份保留天数（过期由 cron 内 find 清理）
     */
    public function installCron(bool $autoExecute, string $time = '03:00', int $keepDays = 7): void
    {
        $ctx = $this->backupContext();

        if (!preg_match('/^(\d{1,2}):(\d{2})$/', $time, $m) || (int)$m[1] > 23 || (int)$m[2] > 59) {
            deploy_log("time 格式错误: {$time}（应为 HH:MM）", 'error');
            exit(1);
        }
        $keepDays = max(1, $keepDays);
        $cronTime = sprintf('%02d:%02d', (int)$m[1], (int)$m[2]);
        [$h, $min] = explode(':', $cronTime);

        $this->ssh->connect();
        try {
            if (trim($this->ssh->exec("command -v crontab >/dev/null 2>&1 && echo YES || echo NO", false)) !== 'YES') {
                deploy_log('服务器未安装 crontab（可执行: apt install cron）', 'error');
                exit(1);
            }

            // 备份文件名由 cron 运行时生成（% 需转义为 \%）；
            // 重定向目标用引号分段拼接，让 $(date ...) 在单引号外可展开
            $prefix = safe_name($ctx['project']) . '_' . safe_name($ctx['db']) . '_';
            $redirect = $this->rq($ctx['dir']) . '/' . $this->rq($prefix)
                . '$(date +\%Y\%m\%d_\%H\%M)' . $this->rq('.sql.gz');
            $cronLine = "{$min} {$h} * * * " . $this->dumpCmd($ctx, $redirect)
                . " && find " . $this->rq($ctx['dir']) . " -name '*.sql.gz' -mtime +{$keepDays} -delete";

            $block = "# BEGIN deploy-backup {$ctx['project']}\n{$cronLine}\n# END deploy-backup {$ctx['project']}\n";

            if (!$autoExecute) {
                echo "\n";
                deploy_log('将写入以下 crontab 条目（暂未执行，加 -y 生效）:', 'step');
                echo "\n{$block}\n";
                deploy_log("php admin app:{$ctx['project']} db:backup:cron -y time={$cronTime} keep={$keepDays}", 'info');
                return;
            }

            // 确保 cron 服务在运行（尽力而为）
            $this->ssh->exec("systemctl enable --now cron 2>/dev/null || service cron start 2>/dev/null || true", false);

            $existing = $this->ssh->exec("crontab -l 2>/dev/null", false);
            $newCrontab = $this->replaceBackupBlock($existing, $ctx['project'], $block);

            $this->ssh->uploadContent($newCrontab, '/tmp/deploy-crontab');
            $this->ssh->exec("crontab /tmp/deploy-crontab && rm -f /tmp/deploy-crontab", false);

            if (!str_contains($this->ssh->exec("crontab -l 2>/dev/null", false), "# BEGIN deploy-backup {$ctx['project']}")) {
                deploy_log('定时备份安装失败', 'error');
                exit(1);
            }

            deploy_log("定时备份已安装: 每天 {$cronTime}，保留 {$keepDays} 天", 'ok');
            deploy_log("备份目录: {$ctx['dir']}", 'info');
            deploy_log("验证: php admin app:{$ctx['project']} db:backup:list", 'info');
        } finally {
            $this->ssh->disconnect();
        }
    }

    /**
     * 移除定时备份（保留服务器上已生成的备份文件）
     */
    public function removeCron(): void
    {
        $ctx = $this->backupContext();

        $this->ssh->connect();
        try {
            $existing = $this->ssh->exec("crontab -l 2>/dev/null", false);
            if (!str_contains($existing, "# BEGIN deploy-backup {$ctx['project']}")) {
                deploy_log('未发现该项目的定时备份任务', 'warn');
                return;
            }

            $newCrontab = $this->replaceBackupBlock($existing, $ctx['project'], '');
            if (trim($newCrontab) === '') {
                $this->ssh->exec("crontab -r 2>/dev/null || true", false);
            } else {
                $this->ssh->uploadContent($newCrontab, '/tmp/deploy-crontab');
                $this->ssh->exec("crontab /tmp/deploy-crontab && rm -f /tmp/deploy-crontab", false);
            }

            deploy_log('定时备份已移除（已生成的备份文件保留在服务器）', 'ok');
        } finally {
            $this->ssh->disconnect();
        }
    }

    /**
     * 替换 crontab 中本项目的备份标记块（不存在时追加）
     * $block 为空串表示仅移除
     */
    protected function replaceBackupBlock(string $crontab, string $project, string $block): string
    {
        $begin = '# BEGIN deploy-backup ' . $project;
        $end = '# END deploy-backup ' . $project;
        $pattern = '/' . preg_quote($begin, '/') . '\n.*?' . preg_quote($end, '/') . '\n?/s';

        if (preg_match($pattern, $crontab)) {
            $result = preg_replace($pattern, '', $crontab, 1);
        } else {
            $result = $crontab;
        }

        $result = rtrim($result);
        if ($block !== '') {
            $result .= ($result === '' ? '' : "\n\n") . $block;
        }
        return $result . ($result === '' ? '' : "\n");
    }

    /**
     * 下载单个备份文件到本地 deploy/backups/<项目>/
     */
    protected function downloadFile(array $ctx, string $file, string $remoteFile): void
    {
        $localDir = deploy_base_path() . '/backups/' . $ctx['project'];
        if (!is_dir($localDir)) {
            mkdir($localDir, 0755, true);
        }
        $localFile = $localDir . '/' . $file;
        $this->ssh->download($remoteFile, $localFile);
        $size = round(filesize($localFile) / 1024, 1);
        deploy_log("已下载: {$localFile}（{$size} KiB）", 'ok');
    }

    /* ---------------- 内部方法 ---------------- */

    /**
     * 远程 shell 引用（Linux 单引号转义）
     */
    protected function rq(string $value): string
    {
        return "'" . str_replace("'", "'\\''", $value) . "'";
    }

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
