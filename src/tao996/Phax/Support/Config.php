<?php

namespace Phax\Support;


use Phalcon\Config\Exception;
use Phalcon\Di\Di;
use Phax\Utils\MyFileSystem;

class Config
{
    /**
     * @var string 本地静态资源，因为 workerman 中的资源无法像 nginx 一样做映射，所以需要使用网址
     */
    public static string $local_assets_origin = '';
    /**
     * 当前项目
     * @var string
     */
    private string $current_project = '';
    private static \Phalcon\Config\Config $config;
    /**
     * 项目配置，只在 workerman 下有效
     * @var array
     */
    private static array $projects_config = [];

    public function __construct(public Di $di)
    {
    }

    /**
     * 加载配置
     */
    public function load(): \Phalcon\Config\Config
    {
        $global_config_file = '';
        foreach (
            [
                env('PATH_CONFIG'),
                PATH_CONFIG . 'config.php',
                PATH_CONFIG . 'config.demo.php'
            ] as $file
        ) {
            if (file_exists($file)) {
                $global_config_file = $file;
            }
        }
        if (empty($global_config_file)) {
            throw new \Exception('could not find global config file');
        }
        self::$config = $this->parse($global_config_file);

        if (IS_WORKER_WEB) {
//            TODO 暂时未实现，因为会涉及到全部的服务，后期再看是否需要支持此特性
//            if ($projects = MyFileSystem::findInDirs(PATH_APP_PROJECTS, 'dir')) {
//                foreach ($projects as $project) {
//                    $configFilePath = PATH_APP_PROJECTS . $project . '/Config/config.php';
//                    if (file_exists($configFilePath)) {
//                        $project_cc = $this->parse($configFilePath);
//                        $project_cc->merge(self::$config);
//                        self::$projects_config[$project] = $project_cc;
//                    }
//                }
//            }
        } else {
            if ($this->current_project = $this->getProject()) {
                $configFilePath = PATH_APP_PROJECTS . $this->current_project . '/Config/config.php';
                if (file_exists($configFilePath)) {
                    $project_cc = $this->parse($configFilePath);
                    self::$config->merge($project_cc);
                }
            }
        }
        return self::$config;
    }

    /**
     * @throws Exception
     */
    private function parse(string $filepath): \Phalcon\Config\Config
    {
        $cc = new \Phalcon\Config\Config();
        $cc->merge(include_once $filepath);
        return $cc;
    }

    /**
     * @param string $path
     * @param mixed|null $default
     * @return \Phalcon\Config\Config|string|bool|int|null
     */
    public function path(string $path, mixed $default = null): mixed
    {
        return static::$config->path($path, $default);
    }

    /**
     * 读取全局配置文件
     * @param string $path
     * @param mixed|null $default
     * @return \Phalcon\Config\Config|string|bool|int
     */
    private function globalPath(string $path, mixed $default = null): \Phalcon\Config\Config|string|bool|int
    {
        return static::$config->path($path, $default);
    }


    /**
     * 是否是演示环境
     * @return bool
     */
    public function isDemo(): bool
    {
        return $this->globalPath('app.demo', false);
    }

    /**
     * 是否开启测试环境
     * @return bool
     */
    public function isTest(): bool
    {
        return $this->globalPath('app.test.open', false);
    }

    public function getTestUsers(): array
    {
        return $this->globalPath('app.test.tokens', [])?->toArray() ?: [];
    }

    public function getSuperAdminIds(): array
    {
        return $this->globalPath('app.superAdmin', [])?->toArray() ?: [];
    }

    /**
     * 获取当前访问的项目
     * @return string
     */
    public function getProject(): string
    {
        if (!IS_WORKER_WEB) {
            if (!empty($this->current_project)) {
                return $this->current_project;
            }
        }
        if ($host = $this->getHost()) {
            if ($sites = $this->globalPath('app.sites', [])?->toArray()) {
                foreach ($sites as $project => $hosts) {
                    if (in_array($host, $hosts)) {
                        return $project;
                    }
                }
            }
        }
        return $this->globalPath('app.default') ?: '';
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
}