<?php

namespace Phax\Foundation;

use Phalcon\Di\Di;
use Phalcon\Http\ResponseInterface;
use Phax\Mvc\Controller;
use Phax\Support\Config;
use Phax\Support\Env;
use Phax\Support\Exception\BlankException;
use Phax\Support\Exception\LocationException;
use Phax\Support\Logger;
use Phax\Support\Router;
use Phax\Utils\Color;
use Phax\Utils\MyData;

/**
 * 记录每个应用的 di/config 等信息
 */
class Application
{

    public static function di(): Di
    {
        return DiService::defaultContainer();
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
        if (file_exists(PATH_ROOT . '.env')) {
            Env::load(PATH_ROOT . '.env');
        }
        define('IS_DEBUG', env('APP_DEBUG', '') === 'true');
        DiService::with($di)
            ->config(function (\Phalcon\Config\Config $config) {
                date_default_timezone_set($config->path('app.timezone'));
                if ($namespaces = $config->path('app.loader.namespaces', [])->toArray()) {
                    loader()->setNamespaces($namespaces, true)
                        ->register();
                }
                foreach ($config->path('app.loader.includes', [])->toArray() as $f) {
                    include_once $f;
                }
            })
            ->logger()
            ->crypt()
            ->modelsMetadata()
            ->profiler()
            ->security();
    }

    /**
     * @param string|null $requestURL 请求的 URL，通常为 $_SERVER['REQUEST_URI']
     * @throws \Exception
     */
    public function runWeb(string|null $requestURL = null): ?\Phalcon\Http\ResponseInterface
    {
        $di = self::di();
        DiService::with($di)->db()->pdo()->redis()->cache()->flash()
            ->session()->cookies()->url()->router()->view()
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
        } catch (LocationException $e) {
            header('Location:' . $e->getMessage(), true, 302);
            return null;
        } catch (BlankException $e) {
            echo $e->getMessage();
            return null;
        } catch (\Exception $e) {
            Logger::error($requestURL);
            echo $this->handleException($e);
            exit;
        }

        return null;
    }

    public function handleException(\Exception $e, Di $di = null): string
    {
        if (IS_DEBUG || $e->getCode() >= 500) {
            Logger::exception($e);
        }

        /**
         * @var Config $config
         */
        $config = self::di()->get('config');
        $errClass = $config->path('app.error');

        if (class_exists($errClass)) {
            /**
             * @var $errObj Controller
             */
            $errObj = new $errClass();
            $errObj->setDI($di ?: self::di());

            if ($e instanceof \Phalcon\Mvc\Dispatcher\Exception) {
                try {
                    return call_user_func_array([$errObj, 'notFound'], [$e]) ?: 'sorry ...';
                } catch (\Exception $e) {
                    return $e->getMessage();
                }
            } else {
                try {
                    return call_user_func_array([$errObj, 'exception',], [$e]) ?: 'sorry ...';
                } catch (\Exception $e) {
                    return $e->getMessage();
                }
            }
        } else {
            return 'could not find the error handle class:' . $errClass;
        }
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
        $route = new Route($requestURL, $di);
        $di->setShared('route', $route);
//        ddd($requestURL,$di->getServices());
        /**
         * @var Config $config
         */
        $config = $di->get('config');
        $options = [
            'module' => Router::$moduleKeyword,
            'project' => $config->getProject(),
        ];
        $route->routerOptions = Router::analysisWithURL($requestURL, $options);

        /**
         * @var \Phalcon\Mvc\Router $router
         */
        $router = $di->getShared('router');
//        require_once PATH_ROOT . 'routes/web.php';

        $router->setDefaultNamespace($route->routerOptions['namespace']);
        // 添加到路由
        // 注意：pattern, route 要和 application->handle($uri) 保持一致
        $router->add($route->routerOptions['route'], $route->routerOptions['paths']);
//        ddd($requestURL, $route->routerOptions,$route->getControllerClass());
        /**
         * @var \Phalcon\Mvc\Application $application
         */
        $application = $di->get('application');
        $application->setDI($di);

        if (isset($route->routerOptions['registerModules'])) {
            $application->registerModules($route->routerOptions['registerModules']);
        }
        if ($route->isApiRequest()) {
            $application->useImplicitView(false);
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
        /**
         * @var $console \Phalcon\Cli\Console
         */
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
            'm' => Router::$moduleKeyword,
            'p' => Router::$projectKeyword,
        ];

        $arguments = [
            'params' => [],
        ];

        foreach ($argv as $k => $arg) {
            if ($k === 1) { // main/test, m/demo/main/say
                $info = CliRouter::handle($arg, $options);
                if (isset($info['modules'])) {
                    $console->registerModules((array)$info['modules']);
                }
                $arguments = array_merge($arguments, $info);
            } elseif ($k >= 2) {
                $argKV = $this->parseKeyValue($arg);
                if (is_array($argKV)) {
                    $arguments['params'] = array_merge($arguments['params'], $argKV);
                } else {
                    $arguments['params'][] = $arg;
                }
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

    private function parseKeyValue($input)
    {
        // 使用正则表达式匹配 --key=value 或 -key=value 的格式
        if (preg_match('/^--?([a-zA-Z0-9_]+)=(.*)$/', $input, $matches)) {
            return [$matches[1] => 'true' === $matches[2] || 'false' === $matches['2'] ? $matches[2] === 'true' : $matches[2]];
        } else {
            return $input;
        }
    }
}