<?php

namespace Phax\Support;


use Phalcon\Config\Exception;
use Phalcon\Di\Di;

class Config
{
    /**
     * 当前项目
     * @var string
     */
    private string $current_project = '';
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
        $configList = [
            PATH_CONFIG . 'config.php',
            PATH_CONFIG . 'config.demo.php'
        ];
        if (!empty(env('PATH_CONFIG'))) {
            array_unshift($configList, PATH_ROOT . env('PATH_CONFIG'));
        }
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
        self::$config = $this->_parse($global_config_file);

        if ($this->current_project = $this->getProject()) {
            $configFilePath = PATH_APP_PROJECTS . $this->current_project . '/Config/config.php';
            if (file_exists($configFilePath)) {
                $project_cc = $this->_parse($configFilePath);
                self::$config->merge($project_cc);
            }
        }
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
     * @param mixed|null $default 如果 path 是一个多组数组，则返回 \Phalcon\Config\Config；如果是普通数组，则返回 array
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
        if (is_string($obj)) {
            return $obj;
        }
        return '';
    }
    public function getInt(string $path, int $default = 0): int
    {
        $obj = self::path($path, $default);
        return (int) $obj;
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


    /**
     * 是否是演示环境
     * @return bool
     */
    public function isDemo(): bool
    {
        return $this->getBoolean('app.demo');
    }

    /**
     * 是否开启测试环境
     * @return bool
     */
    public function isTest(): bool
    {
        return $this->getBoolean('app.test.open');
    }

    public function getTestUsers(): array
    {
        return $this->getArray('app.test.tokens');
    }

    public function getSuperAdminIds(): array
    {
        return $this->getArray('app.superAdmin');
    }

    /**
     * 获取当前访问的项目及站点配置
     * 返回 ['name' => 'family', 'namespace' => '...', 'viewpath' => '...']
     * 如果未匹配到项目，name 为空字符串
     * @return array{name:string,namespace:string,viewpath:string}
     */
    public function getProjectWithConfig(): array
    {
        if (!empty($this->current_project)) {
            return $this->buildProjectConfig($this->current_project);
        }
        if ($host = $this->getHost()) {
            if ($sites = $this->getArray('app.sites')) {
                foreach ($sites as $project => $hosts) {
                    $domains = is_array($hosts) && isset($hosts['domains']) ? $hosts['domains'] : $hosts;
                    if (in_array($host, (array)$domains)) {
                        return $this->buildProjectConfig($project, $hosts);
                    }
                }
            }
        }
        $default = $this->getString('app.default');
        return $default ? $this->buildProjectConfig($default) : ['name' => '', 'namespace' => '', 'viewpath' => ''];
    }

    /**
     * 构建项目的配置
     */
    private function buildProjectConfig(string $project, array $entry = []): array
    {
        $defaultNamespace = 'App\\Projects\\' . $project . '\\Controllers';
        $defaultViewpath = PATH_APP_PROJECTS . $project . DIRECTORY_SEPARATOR . 'views';

        if (is_array($entry) && isset($entry['domains'])) {
            // 扩展格式
            return [
                'name' => $project,
                'namespace' => $entry['namespace'] ?? $defaultNamespace,
                'viewpath' => $entry['viewpath'] ?? $defaultViewpath,
            ];
        }

        // 简单格式或未提供 entry
        return [
            'name' => $project,
            'namespace' => $defaultNamespace,
            'viewpath' => $defaultViewpath,
        ];
    }

    /**
     * 获取当前访问的项目（仅名称，兼容旧代码）
     * @return string
     */
    public function getProject(): string
    {
        return $this->getProjectWithConfig()['name'] ?? '';
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