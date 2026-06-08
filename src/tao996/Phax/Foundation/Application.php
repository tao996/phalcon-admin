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
     */
    public function __construct(string $sourceRoot)
    {
        if (!file_exists($sourceRoot)) {
            exit('could not find the sourceRoot path');
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

        // IP 白名单检查
        $ipWhitelist = $di->get('config')->path('app.ipWhitelist', [])->toArray();
        if (!empty($ipWhitelist)) {
            $clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
            if (!$this->checkIpWhitelist($clientIP, $ipWhitelist)) {
                http_response_code(403);
                exit('您的 IP 地址 (' . $clientIP . ') 不在访问白名单中');
            }
        }

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
        } catch (\Throwable $e) {
            echo $this->handleException($e);
            exit;
        }

        return null;
    }

    public function handleException(\Throwable $e, Di $di = null): string
    {
        Logger::exception($e);

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
                } catch (\Phax\Support\Exception\BlankException $e) {
                    return ''; // JSON 响应已由 $this->json() 通过 send() 发送，无需追加内容
                } catch (\Throwable $_) {
                    return '页面未找到';
                }
            } else {
                try {
                    return call_user_func_array([$errObj, 'exception',], [$e]) ?: 'sorry ...';
                } catch (\Phax\Support\Exception\BlankException $e) {
                    return ''; // JSON 响应已由 $this->json() 通过 send() 发送，无需追加内容
                } catch (\Throwable $_) {
                    return '系统繁忙，请稍后再试2';
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
//        ddd($requestURL, $di->getServices());
        /**
         * @var Config $config
         */
        $config = $di->get('config');
        $defaultApp = $config->path('app.defaultApp')->toArray();
        $project = $config->getProjectWithConfig();

        $options = [
            'module' => Router::$moduleKeyword,
            'project' => $project['name'],
            'projectNamespace' => $project['namespace'] ?: null,
            'projectViewpath' => $project['viewpath'] ?: null,
            'defaultNamespace' => $defaultApp['namespace'] ?? 'App\\Http\\Controllers',
            'defaultViewpath' => $defaultApp['viewpath'] ?? '',
        ];
        if (empty($options['defaultViewpath'])) {
            if ($options['defaultNamespace'] === 'App\\Http\\Controllers') {
                $options['defaultViewpath'] = PATH_APP . 'Http' . DIRECTORY_SEPARATOR . 'views';
            } else {
                if (!str_ends_with($options['defaultNamespace'], '\\Controllers')) {
                    throw new \Exception('自定义命名空间必须以 \\Controllers 结尾');
                }
                if (str_starts_with($options['defaultNamespace'], 'App\\Modules\\')) {
                    $cc = explode('\\', $options['defaultNamespace']);
                    $options['defaultViewpath'] = PATH_APP_MODULES . $cc[2] . '/views';
                } elseif (str_starts_with($options['defaultNamespace'], 'App\\Projects\\')) {
                    $cc = explode('\\', $options['defaultNamespace']);
                    $options['defaultViewpath'] = PATH_APP_PROJECTS . $cc[2] . '/views';
                }

            }
        }
        $route->routerOptions = Router::analysisWithURL($route->urlOptions['mapurl'], $options);
//        ddd($route->urlOptions,$route->routerOptions);

        /**
         * @var \Phalcon\Mvc\Router $router
         */
        $router = $di->getShared('router');

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
        return $application->handle($route->urlOptions['mapurl']);
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
            fwrite(STDERR, get_class($e) . ': ' . $e->getMessage() . PHP_EOL);
            fwrite(STDERR, '  at ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL);
            if (defined('IS_DEBUG') && IS_DEBUG) {
                fwrite(STDERR, PHP_EOL . $e->getTraceAsString() . PHP_EOL);
            }
            exit(1);
        } catch (\Throwable $throwable) { // parent of \Exception
            fwrite(STDERR, get_class($throwable) . ': ' . $throwable->getMessage() . PHP_EOL);
            fwrite(STDERR, '  at ' . $throwable->getFile() . ':' . $throwable->getLine() . PHP_EOL);
            if (defined('IS_DEBUG') && IS_DEBUG) {
                fwrite(STDERR, PHP_EOL . $throwable->getTraceAsString() . PHP_EOL);
            }
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

    /**
     * 从控制器命名空间中精准提取模块/项目名称
     * * @param string $namespace 完整的命名空间或类名
     * @return string|null 匹配成功返回名称(xxx)，匹配失败返回 null
     */
    private function extractNameFromNamespace(string $namespace): ?string
    {
        // 🚀 正则解析：
        // ^App\\                   -> 必须以 App\ 开头
        // (Modules|Projects)       -> 第二级必须是 Modules 或 Projects 之一
        // \\([a-zA-Z0-9_]+)        -> 第三级 xxx 为我们要捕获的名称
        // \\Controllers?           -> 最后一级匹配 Controller 或 Controllers
        $pattern = '/^App\\\\(Modules|Projects)\\\\([a-zA-Z0-9_]+)\\\\Controllers?$/';

        // 去掉可能传入的末尾反斜杠
        $namespace = rtrim($namespace, '\\');

        if (preg_match($pattern, $namespace, $matches)) {
            // $matches[1] 是 Modules 或 Projects
            // $matches[2] 就是我们需要的 xxx
            return $matches[2];
        }

        return null;
    }

    /**
     * 检查 IP 是否在白名单中
     * @param string $ip 客户端 IP 地址
     * @param array $whitelist IP 白名单列表（支持精确/CIDR/通配符）
     * @return bool
     */
    private function checkIpWhitelist(string $ip, array $whitelist): bool
    {
        $ipLong = ip2long($ip);
        if ($ipLong === false) {
            return false; // 非法 IP 格式，拒绝
        }

        foreach ($whitelist as $rule) {
            $rule = trim($rule);
            if ($rule === '') {
                continue;
            }

            // 1. CIDR 格式：192.168.1.0/24
            if (str_contains($rule, '/')) {
                [$subnet, $bits] = explode('/', $rule, 2);
                $subnetLong = ip2long($subnet);
                if ($subnetLong === false || !is_numeric($bits)) {
                    continue;
                }
                $mask = -1 << (32 - (int)$bits);
                if (($ipLong & $mask) === ($subnetLong & $mask)) {
                    return true;
                }
                continue;
            }

            // 2. 通配符格式：192.168.* 或 192.168.*.*
            if (str_contains($rule, '*')) {
                $pattern = '/^' . str_replace(['.', '*'], ['\.', '\d+'], $rule) . '$/';
                if (preg_match($pattern, $ip)) {
                    return true;
                }
                continue;
            }

            // 3. 精确匹配
            if ($ip === $rule) {
                return true;
            }
        }

        return false;
    }
}