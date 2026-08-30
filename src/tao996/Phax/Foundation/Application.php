<?php

namespace Phax\Foundation;

use Phalcon\Di\Di;
use Phalcon\Http\ResponseInterface;
use Phax\Foundation\Context\RouteMatchContext;
use Phax\Mvc\Controller;
use Phax\Support\Exception\BlankException;
use Phax\Support\Exception\BusinessException;
use Phax\Support\Exception\LocationException;
use Phax\Support\Logger;
use Phax\Support\Router;
use Phax\Utils\MyColor;

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

        DiService::with($di)
            ->config(function (\Phax\Support\Config $config) {
                date_default_timezone_set($config->getString('app.timezone', 'UTC'));
                if ($namespaces = $config->getArray('app.loader.namespaces')) {
                    loader()->setNamespaces($namespaces, true)
                        ->register();
                }
                foreach ($config->getArray('app.loader.includes') as $f) {
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
        $requestURL = $requestURL ?: $_SERVER['REQUEST_URI'];
        if ($requestURL == '/favicon.ico') {
            die('/favicon.ico');
        }
        $di = self::di();
        DiService::with($di)->db()->pdo()->redis()->cache()->flash()->flashSession()
            ->session()->cookies()->url()->router()->view()
            ->application();


        // IP 白名单检查
        $ipWhitelist = AppService::config()->getArray('app.ipWhitelist');
        if (!empty($ipWhitelist)) {
            $clientIP = $_SERVER['REMOTE_ADDR'] ?? '';
            if (!$this->checkIpWhitelist($clientIP, $ipWhitelist)) {
                http_response_code(403);
                exit('您的 IP 地址 (' . $clientIP . ') 不在访问白名单中');
            }
        }

        try {
            if (file_exists(PATH_ROOT . 'routes/web.php')) {
                require_once PATH_ROOT . 'routes/web.php';
            }
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
        // BusinessException 为普通业务异常（如验证失败、业务规则冲突），
        // 属于预期行为，不记录错误日志，减少日志噪音
        if ($e instanceof BusinessException) {
            if (IS_DEBUG) {
                Logger::exception($e, $e->getContext());
            }
        } else {
            Logger::exception($e);
        }

        $errClass = AppService::config()->getString('app.error', 'App\Http\AppErrorResponse');

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
                } catch (\Throwable $e) {
                    if (IS_DEBUG) {
                        ddd($e->getMessage(), $e->getTrace());
                    } else {
                        Logger::exception($e, ['未被处理的 Throwable']);
                    }
                    return '系统繁忙，请稍后再试。';
                }
            }
        } else {
            return '没有找到错误处理类:' . $errClass;
        }
    }

    /**
     * 处理请求链接
     * @param string $requestURL
     * @param Di $di
     * @return ResponseInterface
     * @throws \Exception
     */
    public function routeWith(string $requestURL, Di $di): \Phalcon\Http\ResponseInterface
    {
        if (isset(RouteMatchContext::$mapRoute[$requestURL])) {
            $requestURL = RouteMatchContext::$mapRoute[$requestURL];
        }
        $context = RouteMatchContext::with($requestURL, loadDefault: true);
        $di->setShared('context', $context);
        /**
         * @var \Phalcon\Mvc\Router $router
         */
        $router = AppService::router();
        $router->setDefaultNamespace($context->namespace);
        // 添加到路由
        // 注意：pattern, route 要和 application->handle($uri) 保持一致
        $router->add($context->route, $context->paths);
//        ddd($requestURL, $route->routerOptions,$route->getControllerClass());
        /**
         * @var \Phalcon\Mvc\Application $application
         */
        $application = $di->get('application');
        $application->setDI($di);

        if ($context->registerModules) {
            $application->registerModules($context->registerModules);
        }
        if ($context->isApiRequest()) {
            $application->useImplicitView(false);
        }
        return $application->handle($context->mapurl);
    }

    private function _loadDefaultCommands(): void
    {

        // php artisan test 运行测试
        // 测试指定文件
        // php artisan tests/Helper/MyTestCurlTest.php
        // 指定文件，指定类
        // php artisan --filter MyTestCurlTest tests/Helper/MyTestCurlTest.php
        /**
         * 测试单个方法
         * # 只执行 MyTestCurlTest 类里的 testGetInfo 方法
         * ./vendor/bin/phpunit --filter "MyTestCurlTest::testGetInfo"
         *
         * # 在指定文件内，只跑 testPostData 方法
         * ./vendor/bin/phpunit --filter testPostData tests/Helper/MyTestCurlTest.php
         */
        CliRouter::add('test', function ($params) {
            system('php ' . PATH_ROOT . 'vendor/bin/phpunit --configuration ' . PATH_ROOT . 'phpunit.xml ' . join(' ', $params));
        }, "run phpunit test with phpunit.xml");

        // refresh meta-data when you update the model
        CliRouter::add('metadata', function () {
            \Phax\Foundation\Application::di()->get('metadata')->reset();
            echo "refresh metadata success", PHP_EOL;
        }, 'refresh all Model metadata');


        /**
         * run `php artisan migration` to see the help
         * https://tao996.github.io/phalcon-admin-docs/#/zh-cn/migration
         */
        CliRouter::add('migration', function () {
            if (file_exists(PATH_TAO996_PHAR . 'phalcon-migrations.phar')) {
                include_once PATH_TAO996_PHAR . 'phalcon-migrations.phar';
            } else {
                throw new \Exception('phalcon-migrations.phar not found');
            }
            print PHP_EOL . MyColor::colorize('Phalcon Migrations', MyColor::FG_GREEN, MyColor::AT_BOLD) . PHP_EOL . PHP_EOL;
            try {
                $migration = new \Phax\Helper\MigrationHelper(true);
                $argv = empty($_SERVER['argv']) ? [] : $_SERVER['argv'];
                array_shift($argv);
                $migration->parser($argv);
            } catch (\Exception $e) {
                echo MyColor::fatal($e->getMessage());
                echo MyColor::fatal($e->getTraceAsString());
                exit(1);
            }
            // src/phalcon-migrations/src/Console/Commands/Migration.php
        }, 'migration db data');

// [codeception](https://codeception.com/docs/GettingStarted)
        CliRouter::add('cc', function ($params) {
            if (empty($params)) {
                // vendor/bin/codecept run
                // vendor/bin/codecept run Acceptance                       # 指定套件
                // vendor/bin/codecept run Acceptance SigninCest.php        # 指定套件下的测试用例
                // vendor/bin/codecept run tests/Acceptance/SigninCest.php  # 指定用例
                // vendor/bin/codecept run tests/Acceptance/backend         # 指定目录
                // vendor/bin/codecept run tests/Acceptance/backend:^login  # 指定目录下的用例
                $params = ['run'];
            } elseif ('b' == $params[0]) {
                $params[0] = 'bootstrap';
            } elseif ('gu' == $params[0]) { // vendor/bin/codecept generate:test unit YourTestClass
                $params[0] = 'generate:test Unit';
            } elseif ('ru' == $params[0]) {
                $params[0] = 'run Unit';
            } elseif ('gc' == $params[0]) { // 集成测试
                // php vendor/bin/codecept generate:cest Acceptance Signin
                // This will generate the SigninCest.php file inside the tests/Acceptance directory
                $params[0] = 'generate:cest Acceptance';
            } elseif ('rc' == $params[0]) {
                $params[0] = 'run Acceptance';
            }
            system('php ' . PATH_ROOT . 'vendor/bin/codecept ' . join(' ', $params), $code);
        }, '使用 cc 来代替 vendor/bin/codecept，以方便执行命令');

        CliRouter::add(['minify', 'min', 'mini'], function ($params) {
            $config = \Phax\Foundation\AppService::config();
            $minify = $config->getArray('app.minify');
            if ($minify) {
                require_once PATH_TAO996_PHAR . 'minify.phar';
                foreach ($minify as $key => $files) {
                    if (!in_array($key, ['js', 'css'])) {
                        echo MyColor::colorize('当前只支持 css/js 不支持:' . $key, MyColor::FG_GREEN, MyColor::AT_BOLD) . PHP_EOL;
                        exit(1);
                    }
                    foreach ($files as $file) {
                        $minifier = $key == 'css' ? new MatthiasMullie\Minify\CSS() : new MatthiasMullie\Minify\JS();
                        if (!file_exists($file)) {
                            throw new \Exception('待压缩文件不存在:' . $file);
                        }
                        $minifier->add($file);
                        $pathinfo = pathinfo($file);
                        // 保存的路径
                        $savepath = $pathinfo['dirname'] . DIRECTORY_SEPARATOR . $pathinfo['filename'] . '.min.' . $pathinfo['extension'];
                        $minifier->minify($savepath);
                    }

                }
                echo '压缩成功', PHP_EOL;
            } else {
                echo '没有需要压缩的 js/css 文件', PHP_EOL;
            }
        }, '压缩 js/css 文件');
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
        if (file_exists(PATH_ROOT . 'routes/cli.php')) {
            include_once PATH_ROOT . 'routes/cli.php';
        }

        $this->_loadDefaultCommands();

        if ($argc < 2 || in_array($argv[1], ['help', '-help', '--help'])) {
            $outputs = [
                MyColor::head('| examples'),
                'artisan main                # run task App\Console\MainTask->indexAction()',
                'artisan main/demo 15        # run task App\Console\MainTask->demoAction(15)',
                'artisan p/demo/main/say 15  # run task App\Projects\demo\Console\MainTask->sayAction(15)',
                'artisan m/demo/main/say 15  # run task App\Modules\demo\Console\MainTask->sayAction(15)',
                MyColor::head('| tool commands'),
            ];
            foreach (CliRouter::find() as $cmd => $desc) {
                $outputs[] = MyColor::spacesPrint('artisan ' . $cmd) . '  # ' . $desc;
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