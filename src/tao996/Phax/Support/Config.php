<?php

namespace Phax\Support;


use Phalcon\Di\Di;

class Config
{
    /**
     * 项目配置数据
     * @var array|mixed
     */
    private static array $projectConfig = [];
    /**
     * 当前项目
     * @var string
     */
    private string $currentProject = '';

    public function __construct(public Di $di)
    {
        static::$projectConfig = include_once PATH_CONFIG . 'project.php';
    }

    /**
     * 获取当前访问的域名
     * @return string
     */
    private function getHost(): string
    {
        if (IS_WEB) {
            /**
             * @var \Phalcon\Http\Request $request
             */
            $request = $this->di->getShared('request');
            if ($request->hasServer('HTTP_X_FORWARDED_HOST')) {
                return str_replace('www.', '', $request->getServer('HTTP_X_FORWARDED_HOST'));
            }
            if ($request->hasServer('HTTP_HOST')) {
                return preg_replace('|:\d+$|', '', $request->getServer('HTTP_HOST'));
            }
        }
        return '';
    }

    /**
     * 是否是演示环境
     * @return bool
     */
    public function isDemo(): bool
    {
        return static::$projectConfig['demo'] ?? false;
    }

    /**
     * 是否开启测试环境
     * @return bool
     */
    public function isTest(): bool
    {
        return static::$projectConfig['test']['open'] ?? false;
    }

    public function getTestUsers(): array
    {
        return static::$projectConfig['test']['tokens'] ?? [];
    }
    public function getSuperAdminIds():array
    {
        return static::$projectConfig['superAdmin'] ?? [];
    }

    /**
     * 获取当前访问的项目
     * @return string
     */
    public function getProject(): string
    {
        if (!empty($this->currentProject)) {
            return $this->currentProject;
        }
        $host = $this->getHost();
        if ($host && !empty(static::$projectConfig['sites'])) {
            foreach (static::$projectConfig['sites'] as $project => $hosts) {
                if (in_array($host, $hosts)) {
                    return $project;
                }
            }
        }
        return static::$projectConfig['default'] ?: '';
    }


    /**
     * 加载配置
     */
    public function load(): \Phalcon\Config\Config
    {
        $this->currentProject = $this->getProject();
        $isDemo = $this->isDemo();
        // 应用配置是否存在
        if ($this->currentProject) {
            $configFilePath = PATH_APP_PROJECTS . $this->currentProject . '/Config/';
            if ($isDemo) {
                $configFilePath .= 'config.demo.php';
            } else {
                $configFilePath .= 'config.php';
            }
            if (file_exists($configFilePath)) {
                return $this->parse($configFilePath);
            }
        }
        // 指定了配置文件
        if ($path = env('PATH_CONFIG')) {
            return $this->parse($path);
        }
        // 默认配置文件
        if ($isDemo) {
            $path = PATH_CONFIG . 'config.demo.php';
            if (file_exists($path)) {
                return $this->parse($path);
            }
        }

        $path = PATH_CONFIG . 'config.php';
        if (file_exists($path)) {
            return $this->parse($path);
        }

        throw new \Exception('could not find config.php file');
    }

    public function parse(string $filepath): \Phalcon\Config\Config
    {
        $cc = new \Phalcon\Config\Config();
        $cc->merge(include_once $filepath);
        self::$config = $cc;
        return $cc;
    }

    /**
     * @var \Phalcon\Config\Config
     */
    private static \Phalcon\Config\Config $config;

    public function path(string $path, mixed $default = null): \Phalcon\Config\Config|string|bool|int
    {
        return static::$config->path($path, $default);
    }

}