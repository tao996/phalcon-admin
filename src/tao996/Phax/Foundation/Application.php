<?php

namespace Phax\Foundation;

use Phalcon\Di\Di;
use Phalcon\Http\ResponseInterface;
use Phax\Support\Config;
use Phax\Support\Env;
use Phax\Support\Exception\BlankException;
use Phax\Support\Logger;
use Phax\Support\Router;
use Phax\Utils\Color;

/**
 * 记录每个应用的 di/config 等信息
 */
class Application
{
    private static Di $di;

    public static function di(): Di
    {
        if (empty(self::$di)) {
            self::$di = DiService::createDi();
        }
        return self::$di;
    }

    /**
     * @param string $sourceRoot 源码根目录
     * in docker , the basePath is /var/www, which put you source code files
     * @throws BlankException
     */
    public function __construct(string $sourceRoot)
    {
        if (!file_exists($sourceRoot)) {
            appExit('could not find the source root path： /xxx/src => /var/www');
        }
    }


    public function autoloadServices(Di $di = null): void
    {
        if (empty($di)) {
            $di = self::di();
        }

        Env::load(PATH_ROOT . '.env');
        define('IS_DEBUG', (bool)env('APP_DEBUG', false));

        // 配置文件
        $cc = new Config($di);
        $di->setShared('config', $cc);

        $config = $cc->load();
        date_default_timezone_set($config->path('app.timezone'));
        $di->setShared('logger', function () use ($cc) {
            $cc = $cc->path('logger')->toArray();
            $params = $cc['stores'][$cc['driver']];

            switch (strtolower($cc['driver'])) {
                case 'stream':
                    $path = $params['path'];
                    preg_match('|{(\w+)}|', $path, $matches);
                    if (!empty($matches)) {
                        $path = str_replace($matches[0], date($matches[1]), $path);
                    }
                    $adapter = new \Phalcon\Logger\Adapter\Stream($path);
                    break;
                case 'syslog':
                    $adapter = new \Phalcon\Logger\Adapter\Syslog(
                        $params['ident'],
                        ['option' => LOG_NDELAY, 'facility' => LOG_MAIL]
                    );
                    break;
                case 'noop':
                    $adapter = new \Phalcon\Logger\Adapter\Noop('nothing');
                    break;
                default:
                    $adapter = new \Phalcon\Logger\Adapter\Stream('php://stderr');
            }
            // https://docs.phalcon.io/5.0/en/logger#creating-a-logger
            return new \Phalcon\Logger\Logger(
                $params['level'],
                [
                    $params['name'] => $adapter,
                ]
            );
        });
        // 注册加密
        $di->setShared('crypt', function () use ($cc) {
            $cc = $cc->path('crypt')->toArray();

            $crypt = new \Phalcon\Encryption\Crypt();
            if ($cc['key']) {
                $crypt->setKey($cc['key']);
            }
            if ($cc['padding']) {
                $crypt->setPadding($cc['padding']);
            }
            $crypt->setCipher($cc['cipher']);
            return $crypt;
        });
        // https://docs.phalcon.io/5.0/en/db-models-metadata
        $di->setShared('modelsMetadata', function () use ($cc) {
            $cc = $cc->path('metadata')->toArray();
            switch ($cc['driver']) {
                case 'apcu':
                    $factory = new \Phalcon\Storage\SerializerFactory();
                    $adapter = new \Phalcon\Cache\AdapterFactory($factory);
                    return new \Phalcon\Mvc\Model\MetaData\Apcu($adapter, $cc['stores']['apcu']);
                case 'memcached':
                    $factory = new \Phalcon\Storage\SerializerFactory();
                    $adapter = new \Phalcon\Cache\AdapterFactory($factory);
                    return new \Phalcon\Mvc\Model\MetaData\Libmemcached($adapter, $cc['stores']['memcached']);
                case 'redis':
                    $factory = new \Phalcon\Storage\SerializerFactory();
                    $adapter = new \Phalcon\Cache\AdapterFactory($factory);
                    return new \Phalcon\Mvc\Model\MetaData\Redis($adapter, $cc['stores']['redis']);
                case 'stream':
                    return new \Phalcon\Mvc\Model\MetaData\Stream($cc['stores']['stream']);
                default:
                    return new \Phalcon\Mvc\Model\Metadata\Memory();
            }
        });
        $di->setShared('profiler', function () {
            return new \Phalcon\Db\Profiler();
        });
        $di->setShared('security', function () {
            return new \Phalcon\Encryption\Security();
        });
        if ($namespaces = $cc->path('loader.namespaces', [])->toArray()) {
            loader()->setNamespaces($namespaces, true)
                ->register();
        }
        foreach ($cc->path('loader.includes', [])->toArray() as $f) {
            include_once $f;
        }
    }

    /**
     * @param string $requestURL 请求的 URL，通常为 $_SERVER['REQUEST_URI']
     * @throws \Exception
     */
    public function runWeb(string $requestURL = ''): ?\Phalcon\Http\ResponseInterface
    {
        $di = self::di();
//        ddd($di->getServices(),__FILE__);
        $diServices = new DiService($di);
        $diServices->db()
            ->pdo()->redis()->cache()
            ->session()->cookie()
            ->url()->flash()->router()->view()
            ->application();

        $requestURL = $requestURL ?: $_SERVER['REQUEST_URI'];
        try {
            if ($response = $this->routeWith($requestURL, $di)) {
                if ($response->isSent()) {
                    echo $response->getContent();
                } else {
                    return $response->send();
                }
            }
        } catch (BlankException $e) {
            echo $e->getMessage();
            return null;
        } catch (\Exception $e) {
            // 服务器内部错误需要记录
            if (is_debug() || $e->getCode() >= 500) {
                Logger::exception($e);
            }
            /**
             * @var Config $config
             */
            $config = $di->get('config');
            $errClass = $config->path('app.error');

            if (class_exists($errClass)) {
                if ($e instanceof \Phalcon\Mvc\Dispatcher\Exception) {
                    try {
                        call_user_func_array([new $errClass(), 'notFound'], [$e]);
                    } catch (\Exception $e) {
                        echo $e->getMessage();
                    }
                } else {
                    try {
                        call_user_func_array([new $errClass(), 'exception',], [$e]);
                    } catch (\Exception $e) {
                        echo $e->getMessage();
                    }
                }
            } else {
                appExit('could not find the error handle class:' . $errClass);
            }
            return null;
        }

        return null;
    }

    /**
     * route 服务
     * @param string $requestURL
     * @param Di $di
     * @return ResponseInterface
     * @throws \Exception
     */
    public function routeWith(string $requestURL, Di $di): \Phalcon\Http\ResponseInterface
    {
        require_once PATH_ROOT . 'routes/web.php';
        $route = new Route($requestURL, $di);
        $di->setShared('route', $route);
//        ddd($requestURL,$di->getServices());
        /**
         * @var Config $config
         */
        $config = $di->get('config');
        $options = [
            'module' => Router::$modulePrefix,
            'project' => $config->getProject(),
        ];
        $route->routerOptions = Router::analysisWithURL($requestURL, $options);

        /**
         * @var \Phalcon\Mvc\Router $router
         */
        $router = $di->getShared('router');
        $router->setDefaultNamespace($route->routerOptions['namespace']);
        // 添加到路由
        // 注意：pattern, route 要和 application->handle($uri) 保持一致
        $router->add($route->routerOptions['route'], $route->routerOptions['paths']);
//        ddd($requestURL, $route->routerOptions);
        /**
         * @var \Phalcon\Mvc\Application $application
         */
        $application = $di->get('application');
        $application->setDI($di);
        $route = $di->get('route');
        if (isset($route->routerOptions['registerModules'])) {
            $application->registerModules($route->routerOptions['registerModules']);
        }
        return $application->handle($requestURL);
    }


    /**
     * 具体使用示例，请查看 artisan 文件
     * @return void
     * @throws \Exception
     */
    public function runCLI(): void
    {
        global $argv, $argc;

        $di = self::di();
        $diServices = new DiService($di);
        $diServices->db()
            ->pdo()->redis()->cache()
            ->application();

        $console = $di->get('application');
        $console->setDI($di);

        include_once PATH_ROOT . 'routes/cli.php';

        if ($argc < 2 || in_array($argv[1], ['help', '-help', '--help'])) {
            $outputs = [
                Color::head('| examples'),
                'artisan main                # run task App\Console\MainTask->indexAction()',
                'artisan main/demo 15        # run task App\Console\MainTask->demoAction(15)',
                'artisan p/demo/main/say 15  # run task App\Projects\demo\Console\MainTask->sayAction(15)',
                'artisan m/demo/main/say 15  # run task App\Modules\demo\Console\MainTask->sayAction(15)',
                Color::head('| commands in the routes/cli.php'),
            ];
            foreach (CliRouter::find() as $cmd => $desc) {
                $outputs[] = Color::spacesPrint('artisan ' . $cmd) . '  # ' . $desc;
            }
            echo join(PHP_EOL, $outputs), PHP_EOL;
            return;
        }

        if (CliRouter::runWith($argv[1])) {
            return;
        }

        $options = [
            'm' => Router::$modulePrefix,
            'p' => Router::$projectPrefix,
        ];

        $arguments = [];

        foreach ($argv as $k => $arg) {
            if ($k === 1) { // main/test, m/demo/main/say
                $info = CliRouter::handle($arg, $options);
                if (isset($info['modules'])) {
                    $console->registerModules((array)$info['modules']);
                }
                $arguments = array_merge($arguments, $info);
            } elseif ($k >= 2) {
                $arguments['params'][] = $arg;
            }
        }
        $di->get('dispatcher')->setDefaultNamespace($arguments['namespace']);
//        ddd($argv, $arguments);

        try {
            $console->handle($arguments);
        } catch (\Phalcon\Cli\Console\Exception $e) {
            fwrite(STDERR, $e->getMessage() . PHP_EOL);
            exit(1);
        } catch (\Throwable $throwable) { // parent of \Exception
            fwrite(STDERR, $throwable->getMessage() . PHP_EOL);
            exit(1);
        }
    }

}