<?php

namespace tao996;

use Phalcon\Autoload\Loader;
use Phax\Foundation\Application;

class Kernel
{
    public static function getLoader(): Loader
    {
        static $loader = null;
        if ($loader === null) {
            $loader = new Loader();
        }
        return $loader;
    }

    public static function with(string $pathRoot): Kernel
    {
        return new self($pathRoot);
    }

    private function __construct(string $pathRoot)
    {
        if (!defined('PATH_ROOT')) {
            define('PATH_ROOT', trim($pathRoot, '/') . DIRECTORY_SEPARATOR);
        }
        $this->_initPaths();
        $this->_loadComposer();
        $this->_initAutoLoader();
        $this->_initEnv();
    }

    private function _initPaths(): void
    {
        define('PATH_CONFIG', PATH_ROOT . 'config' . DIRECTORY_SEPARATOR);
        define('PATH_APP', PATH_ROOT . 'app' . DIRECTORY_SEPARATOR);
        define('PATH_PUBLIC', PATH_ROOT . 'public' . DIRECTORY_SEPARATOR);
        define('PATH_PUBLIC_UPLOAD', PATH_PUBLIC . 'upload' . DIRECTORY_SEPARATOR);
        define('PATH_STORAGE', PATH_ROOT . 'storage' . DIRECTORY_SEPARATOR);
        define('PATH_STORAGE_DATA', PATH_STORAGE . 'data' . DIRECTORY_SEPARATOR);
        define('PATH_STORAGE_CACHE', PATH_STORAGE . 'cache' . DIRECTORY_SEPARATOR);
        define('PATH_APP_MODULES', PATH_ROOT . 'App' . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR);
        define('PATH_APP_PROJECTS', PATH_ROOT . 'App' . DIRECTORY_SEPARATOR . 'Projects' . DIRECTORY_SEPARATOR);
        define('PATH_TAO996', PATH_ROOT . 'tao996' . DIRECTORY_SEPARATOR);
        define('PATH_TAO996_PHAX', PATH_TAO996 . 'Phax' . DIRECTORY_SEPARATOR);
        define('PATH_TAO996_PHAR', PATH_TAO996 . 'phar' . DIRECTORY_SEPARATOR);
    }

    private function _loadComposer(): void
    {
        if (file_exists(PATH_ROOT . 'vendor/autoload.php')) {
            require_once PATH_ROOT . 'vendor/autoload.php';
        }
    }

    private function _initAutoLoader(): void
    {
        self::getLoader()->setNamespaces([
            'App' => PATH_APP,
            'Phax' => PATH_TAO996_PHAX,
        ], true)->register();
    }

    private function _initEnv(): void
    {
        include_once PATH_TAO996_PHAX . 'Support/Env.php';
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'function.php';

        if (file_exists(PATH_ROOT . '.env')) {
            \Phax\Support\Env::load(PATH_ROOT . '.env');
        }

        if (!defined('IS_DEBUG')) {
            define('IS_DEBUG', \Phax\Support\Env::find('IS_DEBUG', '') === 'true');
        }
    }

    public function setupDisplayErrors(): static
    {
        if (IS_DEBUG) {
            // 1. 强制开启错误显示（防止本地 php.ini 误关）
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');

            // 2. 浏览器端才启用 HTML 格式化高亮，CLI/PHPUnit 下保持纯文本
            if (IS_PHP_FPM) {
                ini_set('html_errors', '1');
            }

            // 3. 开发环境必须用最高严谨度 E_ALL，连微小的 Notice 都不放过，确保金融数据严丝合缝
            error_reporting(E_ALL);
        } else {
            // 4. 安全红线：万一生产环境 php.ini 漏配，代码层做兜底，绝对不向外网暴露任何报错
            ini_set('display_errors', '0');
            ini_set('display_startup_errors', '0');
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
        }
// CLI（PHPUnit）下禁用 xdebug 的 HTML 错误输出
        if (!IS_PHP_FPM && extension_loaded('xdebug')) {
            ini_set('xdebug.mode', 'off');
        }
        // 自定义美化错误页面（超级好用）
        set_error_handler("prettyError");
        return $this;
    }

    public function createApplication(): Application
    {
        $app = new Application(PATH_ROOT);
        $app->autoloadServices();
        return $app;
    }

    /**
     * 创建一个测试 DI 容器
     * @return \Phalcon\Di\Di
     */
    public function createTestDi(): \Phalcon\Di\Di
    {
        $di = Application::di();
        $di->setShared('request', function () {
            return new \Tests\Helper\services\Request();
        });
        $di->setShared('response', function () {
            return new \Tests\Helper\services\Response();
        });
        $di->setShared('session', function () {
            return new \Tests\Helper\services\Session();
        });
        $di->setShared('context', function () {
            return new \Phax\Foundation\Context\RouteMatchContext();
        });
        \Phax\Foundation\DiService::with($di)
            ->config(function (\Phax\Support\Config $config) {
            })
            ->db()
            ->pdo()->redis()->cache()
            ->application();
        return $di;
    }

}