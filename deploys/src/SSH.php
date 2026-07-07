<?php

use phpseclib3\Net\SFTP;
use phpseclib3\Crypt\PublicKeyLoader;

/**
 * SSH 远程执行封装
 * 
 * 基于 phpseclib v3 (SFTP + SSH2)
 * 支持：命令执行、文件上传、文件下载
 */
class DeploySSH
{
    protected SFTP $sftp;
    protected bool $connected = false;
    protected string $host = '';
    protected int $port = 22;

    /**
     * @param array $config 连接配置
     *  - host: IP/域名
     *  - port: SSH 端口 (默认 22)
     *  - user: 用户名
     *  - password: 密码（与 keyFile 二选一）
     *  - keyFile: 私钥文件路径
     *  - keyPassphrase: 私钥密码（可选）
     */
    public function __construct(protected array $config)
    {
        $this->host = $config['host'] ?? '';
        $this->port = $config['port'] ?? 22;
    }

    /**
     * 连接远程服务器
     * SFTP 继承 SSH2，同时支持命令执行和文件传输
     */
    public function connect(): void
    {
        deploy_log("正在连接 {$this->host}:{$this->port} ...", 'info');

        $this->sftp = new SFTP($this->host, $this->port, 30);

        $user = $this->config['user'] ?? 'root';

        $authenticated = false;
        if (!empty($this->config['keyFile'])) {
            $keyPath = $this->config['keyFile'];
            // 展开 ~ 为用户目录
            if (str_starts_with($keyPath, '~')) {
                $home = getenv('HOME') ?: (getenv('USERPROFILE') ?: '');
                $keyPath = $home . substr($keyPath, 1);
            }
            if (!file_exists($keyPath)) {
                deploy_log("私钥文件不存在: {$keyPath}", 'error');
                exit(1);
            }
            $key = PublicKeyLoader::load(file_get_contents($keyPath));
            $passphrase = $this->config['keyPassphrase'] ?? null;
            if ($passphrase) {
                $key = $key->withPassword($passphrase);
            }
            $authenticated = $this->sftp->login($user, $key);
        } elseif (!empty($this->config['password'])) {
            $authenticated = $this->sftp->login($user, $this->config['password']);
        } else {
            deploy_log('未提供认证信息（password 或 keyFile）', 'error');
            exit(1);
        }

        if (!$authenticated) {
            deploy_log("SSH 认证失败: {$user}@{$this->host}", 'error');
            exit(1);
        }

        $this->connected = true;
        deploy_log("已连接 {$user}@{$this->host}", 'ok');
    }

    /**
     * 执行远程命令
     *
     * @param string $command 要执行的命令
     * @param bool $showOutput 是否显示输出（默认显示）
     * @return string 命令输出
     */
    public function exec(string $command, bool $showOutput = true): string
    {
        if (!$this->connected) {
            deploy_log('SSH 未连接', 'error');
            exit(1);
        }

        if ($showOutput) {
            deploy_log($command, 'cmd');
        }

        $output = $this->sftp->exec($command);
        $outputStr = $output ?? '';

        if ($showOutput && !empty(trim($outputStr))) {
            foreach (explode("\n", trim($outputStr)) as $line) {
                echo "    {$line}\n";
            }
        }

        return $outputStr;
    }

    /**
     * 上传文件到远程服务器（SFTP）
     *
     * @param string $localPath 本地文件路径
     * @param string $remotePath 远程文件路径
     */
    public function upload(string $localPath, string $remotePath): void
    {
        if (!$this->connected) {
            deploy_log('SSH 未连接', 'error');
            exit(1);
        }

        deploy_log("上传: {$localPath} → {$remotePath}", 'info');

        $result = $this->sftp->put($remotePath, $localPath, SFTP::SOURCE_LOCAL_FILE);
        if ($result === false) {
            deploy_log("上传失败: {$localPath}", 'error');
        }
    }

    /**
     * 上传字符串内容到远程文件（SFTP）
     *
     * @param string $content 文件内容
     * @param string $remotePath 远程文件路径
     */
    public function uploadContent(string $content, string $remotePath): void
    {
        if (!$this->connected) {
            deploy_log('SSH 未连接', 'error');
            exit(1);
        }

        deploy_log("写入: {$remotePath}", 'info');

        // 写入字符串内容（默认 SOURCE_STRING 模式）
        $result = $this->sftp->put($remotePath, $content);
        if ($result === false) {
            deploy_log("写入失败: {$remotePath}", 'error');
        }
    }

    /**
     * 从远程服务器下载文件
     *
     * @param string $remotePath 远程文件路径
     * @param string $localPath 本地保存路径
     */
    public function download(string $remotePath, string $localPath): void
    {
        if (!$this->connected) {
            deploy_log('SSH 未连接', 'error');
            exit(1);
        }

        deploy_log("下载: {$remotePath} → {$localPath}", 'info');

        $content = $this->sftp->get($remotePath);
        if ($content === false) {
            deploy_log("下载失败: {$remotePath}", 'error');
            return;
        }
        file_put_contents($localPath, $content);
        deploy_log("已保存到: {$localPath}", 'ok');
    }

    /**
     * 确保远程目录存在
     */
    public function ensureDir(string $path): void
    {
        $this->exec("mkdir -p {$path}", false);
    }

    /**
     * 检查远程文件是否存在
     */
    public function fileExists(string $path): bool
    {
        $result = $this->exec("[ -f {$path} ] && echo 'EXISTS' || echo 'NOT_FOUND'", false);
        return trim($result) === 'EXISTS';
    }

    /**
     * 断开连接
     */
    public function disconnect(): void
    {
        if ($this->connected) {
            $this->sftp->disconnect();
            $this->connected = false;
            deploy_log('已断开连接', 'info');
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
