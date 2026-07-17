<?php

namespace Phax\Support;


use Phalcon\Config\Exception;
use Phalcon\Di\Di;

class Config
{
    /**
     * 当前处于活动状态的项目名称
     * @var string
     */
    private string $activeProject = '';
    private static \Phalcon\Config\Config $config;

    public function __construct(public Di $di)
    {
    }

    /**
     * 加载配置
     */
    public function load(): void
    {
        $global_config_file = '';
        $configList = [];
        // 如果指定的配置文件路径（优化加载）
        if (env('PATH_CONFIG')) {
            $configList[] = PATH_ROOT . env('PATH_CONFIG');
        }
        // cli 配置文件
        if (!IS_PHP_FPM) {
            $configList[] = PATH_CONFIG . 'cli.config.php';
        }
        $configList[] = PATH_CONFIG . 'config.php';
        foreach (
            $configList as $file
        ) {
            if (file_exists($file)) {
                $global_config_file = $file;
                break;
            }
        }
        if (empty($global_config_file)) {
            throw new \Exception('could not find global config file');
        }
//        ddd($configList,$global_config_file);
        self::$config = $this->_parse($global_config_file);
    }

    /**
     * @throws Exception
     */
    private function _parse(string $filepath): \Phalcon\Config\Config
    {
        $cc = new \Phalcon\Config\Config();
        $cc->merge(include_once $filepath);
        return $cc;
    }

    /**
     * @param string $path
     * @param mixed|null $default 如果 path 是一个多组数组，则返回 \Phalcon\Config\Config；如果是普通数组，则返回 array；
     * 只要有定义，就会返回值：比如设置的字符串为空，则返回空字符串
     * @return \Phalcon\Config\Config|string|bool|int|null
     */
    public function path(string $path, mixed $default = null): mixed
    {
        return static::$config->path($path, $default);
    }

    /**
     * 查询数组值
     * @param string $path
     * @return array
     */
    public function getArray(string $path, array $default = []): array
    {
        $obj = self::path($path, $default);
        if ($obj instanceof \Phalcon\Config\Config) {
            return $obj->toArray();
        } elseif (is_array($obj)) {
            return $obj;
        }
        return $default;
    }

    /**
     * 查询字符串值
     * @param string $path
     * @return string
     */
    public function getString(string $path, string $default = ''): string
    {
        $obj = self::path($path, $default);
        if (is_string($obj) && !empty($obj)) {
            return $obj;
        }
        return $default;
    }

    public function getInt(string $path, int $default = 0): int
    {
        $obj = self::path($path, $default);
        return (int)$obj;
    }

    /**
     * 查询布尔值
     * @param string $path
     * @return bool
     */
    public function getBoolean(string $path, bool $default = false): bool
    {
        $obj = self::path($path, $default);
        if (is_bool($obj)) {
            return $obj;
        } elseif (is_string($obj)) {
            return !empty($obj);
        } elseif (is_numeric($obj)) {
            return $obj > 0;
        }
        return $default;
    }
}