<?php

/*
* Copyright (c) 2024-present
* Author: tao996<lvshutao@outlook.com>
* 
* For the full copyright and license information, please view the LICENSE.txt
* file that was distributed with this source code.
*/

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SFTP;
use phpseclib3\Net\SSH2;

/**
 * 服务器连接管理
 */
class CmdManager
{

    private SshConfig $cc;
    private G $g;

    public function __construct(G $g, SshConfig $cc)
    {
        $this->g = $g;
        $this->cc = $cc;

        include_once PATH_SRC . 'tao996/phar/phpseclib.phar';
    }

    private function sftp()
    {
        static $sftp = null;
        if (is_null($sftp)) {
            $sftp = new phpseclib3\Net\SFTP($this->cc->config['ip']);
            if (!empty($this->cc->config['password'])) {
                if (!$sftp->login($this->cc->config['username'], $this->cc->config['password'])) {
                    throw new \Exception('sftp 密码登录失败');
                }
            } else {
                $key = PublicKeyLoader::load(
                    file_get_contents($this->cc->config['private_ssh_key'])
                );
                if (!$sftp->login($this->cc->config['username'], $key)) {
                    throw new \Exception('sftp private key 登录失败');
                }
            }
        }
        return $sftp;
    }

    public function ssh2()
    {
        static $ssh = null;
        if (is_null($ssh)) {
            $ssh = new SSH2($this->cc->config['ip']);
            if (!empty($this->cc->config['password'])) {
                if (!$ssh->login($this->cc->config['username'], $this->cc->config['password'])) {
                    throw new \Exception(
                        "SSH2 validation failed using Username: " . $this->cc->config['username'] . " (specify valid password)"
                    );
                }
            } else {
                $key = PublicKeyLoader::load(
                    file_get_contents($this->cc->config['private_ssh_key'])
                );
                if (!$ssh->login(
                    $this->cc->config['username'],
                    $key
                )) {
                    throw new \Exception(
                        "SSH2 validation failed using Username: " . $this->cc->config['username'] . " (specify valid SSH keys or check your SSH key path)"
                    );
                }
            }
        }
        return $ssh;
    }


    /**
     * @param $command
     * @return string|false
     */
    public function sendCommand($command): bool|string
    {
        return $this->ssh2()->exec($command);
    }


    public function sendFile($local_file, $remote_file): bool
    {
        if (!file_exists($local_file)) {
            throw new \Exception('文件不存在 {' . $local_file . '}' . PHP_EOL);
        }
        $remote_dir = dirname($remote_file);
        if (!$this->sftp()->is_dir($remote_dir)) {
            $this->createRemoteDirectoryRecursively($this->sftp(), $remote_dir);
        }
        $rst = $this->sftp()->put($remote_file, $local_file, SFTP::SOURCE_LOCAL_FILE);

        if ($rst) {
            $this->g->messages[] = '上传成功 {' . $remote_file . '}';
        } else {
            echo '本地文件:', $local_file, PHP_EOL;
            echo '远程文件:', $remote_file, PHP_EOL;
            throw new \Exception('上传失败');
        }

        return $rst;
    }


    // 递归创建目录
    private function createRemoteDirectoryRecursively(SFTP $sftp, string $directory): void
    {
        $parentDir = dirname($directory);
        if ($parentDir !== '.' && !$sftp->is_dir($parentDir)) {
            $this->createRemoteDirectoryRecursively($sftp, $parentDir);
        }
        if (!$sftp->is_dir($directory)) {
            $sftp->mkdir($directory);
        }
    }

    /**
     * 获取文件
     */
    public function recvFile($remote_file, $local_file): bool
    {
        $rst = $this->sftp()->get($remote_file, $local_file);

        if ($rst) {
            $this->g->messages[] = '获取成功 {' . $remote_file . '}';
            $this->g->messages[] = '   =>{' . $local_file . '}';
        } else {
            $this->g->messages[] = '获取失败 {' . $remote_file . '}';
        }


        return $rst;
    }

    /**
     * 移除文件
     */
    public function delFile($remote_file): bool
    {
        $rst = $this->sftp()->delete($remote_file);

        if ($rst) {
            $this->g->messages[] = '移除成功 {' . $remote_file . '}';
        } else {
            $this->g->messages[] = '移除失败 {' . $remote_file . '}';
        }

        return $rst;
    }

    private function skipDownloadFile($file): bool
    {
        return in_array($file, ['.', '..', '.gitignore']);
    }

    public function downloadDir($remote_dir, $local_dir): void
    {
        $remote_dir = rtrim($remote_dir, '/') . '/';
        $local_dir = rtrim($local_dir, '/') . '/';

        $sftp = $this->sftp();
        if ($remoteList = $sftp->nlist($remote_dir)) {
            foreach ($remoteList as $item) {
                if (!$this->skipDownloadFile($item)) {
                    if ($sftp->is_dir($remote_dir . $item)) {
                        $localSubDir = $local_dir . $item;
                        if (!is_dir($localSubDir)) {
                            mkdir($localSubDir, 0777, true);
                        }
                        // 递归下载
                        $this->downloadDirectoryRecursive($sftp, $remote_dir . $item, $localSubDir);
                    } else {
                        // 直接下载
                        $this->recvFile($remote_dir . $item, $local_dir . $item);
                    }
                }
            }
        }
    }

    private function downloadDirectoryRecursive(SFTP $sftp, string $remoteSubDir, string $localSubDir): void
    {
        $remoteList = $sftp->nlist($remoteSubDir);
        if ($remoteList) {
            foreach ($remoteList as $item) {
                if (!$this->skipDownloadFile($item)) {
                    if ($sftp->is_dir($remoteSubDir . $item)) {
                        $newLocalSubDir = $localSubDir . $item;
                        if (!is_dir($newLocalSubDir)) {
                            mkdir($newLocalSubDir, 0777, true);
                        }
                        $this->downloadDirectoryRecursive($sftp, $remoteSubDir . $item, $newLocalSubDir);
                    } else {
                        $localFilePath = $localSubDir . $item;
                        $remoteFilePath = $remoteSubDir . $item;
                        $this->recvFile($remoteFilePath, $localFilePath);
                    }
                }
            }
        }
    }
}