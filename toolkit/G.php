<?php

/*
* Copyright (c) 2024-present
* Author: tao996<lvshutao@outlook.com>
* 
* For the full copyright and license information, please view the LICENSE.txt
* file that was distributed with this source code.
*/

use Symfony\Component\Dotenv\Dotenv;

class G
{
    /**
     * read data from PATH_ADMIN_TMP_FILE file
     * @var array{port:int}
     */
    public array $maintainData = [];
    /**
     * 命令行参数
     * @var array
     */
    public array $argsOptions = [];

    /**
     * 当前项目名称
     */
    public string $projectName = '';
    /**
     * 当前操作名称
     * @var string
     */
    public string $action = '';

    /**
     * 生成配置文件的前辍，测试专用
     * @var string
     */
    public string $prefix = '';

    public array $messages = [];

    /**
     * @param array $args 命令行参数，通常直接 $argv
     * @return void
     */
    public function __construct(array $args)
    {
        $this->projectName = pathinfo(PATH_ROOT, PATHINFO_BASENAME);
        if (file_exists(PATH_ADMIN_TMP_FILE)) {
            $this->maintainData = include_once PATH_ADMIN_TMP_FILE;
        }

        $this->argsOptions = $this->loadKvArgs($args);
        $this->loadEnv();
        $this->prefix = $this->getArgsWithKey('prefix');
    }

    /**
     * 保存命令行所指定的数据
     * @return void
     */
    function saveMaintainTmp(): void
    {
        file_put_contents(PATH_ADMIN_TMP_FILE, '<?php return ' . var_export($this->maintainData, true) . ';');
    }

    /**
     * 命令行中是否存在参数
     * @param string $key
     * @return bool
     */
    public function hasArgsWithKey(string $key): bool
    {
        return array_key_exists($key, $this->argsOptions);
    }

    /**
     * 获取参数中的值
     * @param string $key
     * @param mixed $default
     * @param bool $int 是否转为整型
     * @return int|mixed|string
     */
    public function getArgsWithKey(string $key, mixed $default = '', bool $int = false): mixed
    {
        $data = $this->argsOptions[$key] ?? $this->maintainData[$key] ?? $default;
        return $int ? intval($data) : $data;
    }

    /**
     * 获取命令行中的 --key=value 或者 -key=value 或者 -key 参数
     * @return array
     */
    private function loadKvArgs(array $args): array
    {
        if (in_array($args[0], ['admin', './admin', '.\admin'])) {
            array_shift($args); // 移除脚本名称
        }
        $this->action = $args[0] ?? 'help';
        $options = [];
        foreach ($args as $item) {
            if (preg_match('/^(--?)([\w-]+)=(.*)$/', $item, $matches)) {
                $value = $matches[3];
                $key = ltrim($matches[2], "-");
                $options[$key] = $value;
            } elseif (preg_match('/^(--?)([\w-]+)$/', $item, $matches)) {
                $key = ltrim($matches[2], "-");
                $options[$key] = null;
            }
        }
        return $options;
    }

    /**
     * 获取 .env 配置信息
     * @return void
     */
    private function loadEnv(): void
    {
        global $testPrefix;
        $dotenv = new Dotenv();
        if (!file_exists(PATH_ROOT . $testPrefix . '.env')) {
            return;
        }
        $dotenv->overload(PATH_ROOT . $testPrefix . '.env');
    }
}