<?php

namespace Phax\Bridge\Workerman;

use Phax\Support\Exception\LocationException;
use Workerman\Worker;
use Workerman\Protocols\Http\Session;
use Workerman\Protocols\Http\Session\RedisSessionHandler;

use Phalcon\Http\Response\Exception;

use Phax\Foundation\Application;
use Phax\Foundation\DiService;
use Phax\Bridge\Coroutine;

/**
 * 接入 workerman
 */
class WorkermanPhalcon
{
    public DiService $diService;
    public array $sessionRedisOptions = [];
    public array $cookiesOptions = [];

    public function __construct(public Application|null $application, bool $requireWorkermanPhar = true)
    {
        if (!is_null($this->application)) {
            $diServices = new DiService(Application::di());
            $diServices->cache()->flash()
                ->db(false)->redis(false)->pdo(false)
                ->view(false);
            $this->diService = $diServices;

            $sessionCc = $this->diService->getConfig()->path('session')->toArray();
            $ses = $sessionCc['stores']['redis'];
            $this->sessionRedisOptions = [
                'host' => $ses['host'],

                'port' => $ses['port'],
                'timeout' => 2,
                'auth' => $ses['auth'],
                'database' => $ses['index'],
                'prefix' => $ses['prefix'] ?? '_ses_'
            ];
            $this->cookiesOptions = $this->diService->getConfig()->path('cookie')->toArray();
        }
        if ($requireWorkermanPhar) {
            require_once PATH_PHAR . 'workerman.phar';
        }
    }

    /**
     * 设置各种配置文件
     * @param string $name
     * @param array{string} $files
     * @return void
     */
    public function setWorkerFiles(string $name, array $files = ['status', 'log', 'pid']): void
    {
        // 日志 https://www.workerman.net/doc/workerman/worker/log-file.html
        if (in_array('status', $files)) {
            Worker::$statusFile = PATH_ROOT . 'storage/logs/workerman_' . $name . '.status';
        }
        if (in_array('log', $files)) {
            Worker::$logFile = PATH_ROOT . 'storage/logs/workerman_' . $name . '.log';
        }
        if (in_array('pid', $files)) {
            Worker::$pidFile = PATH_ROOT . 'storage/app/workerman_' . $name . '.pid';
        }
    }

    private function withFile(string $file): \Workerman\Protocols\Http\Response
    {
//        print_r(['static file' => $file]);
        $response = new \Workerman\Protocols\Http\Response();
        if ($index = strpos($file, '?')) {
            $file = substr($file, 0, $index);
        }
        $response->withFile($file);
        return $response;
    }

    /**
     * 处理检查静态文件
     * @param \Workerman\Connection\TcpConnection $connection
     * @param \Workerman\Protocols\Http\Request $request
     * @param bool $staticFiles 不建议在这里处理，应该交由 nginx proxy 去处理以提高性能；docker/nginx/assets.example.conf
     * @return bool
     */
    public function staticFile(
        \Workerman\Connection\TcpConnection $connection,
        \Workerman\Protocols\Http\Request $request,
        bool $staticFiles = false
    ): bool {
        $requestURL = $request->uri();
        if ('/favicon.ico' == $requestURL) {
            $connection->send('');
            return true;
        }
        if ($staticFiles) {
            if (str_starts_with($requestURL, '/pstatic/')) {
                preg_match('|/pstatic/([^/]+)(.+)|', $requestURL, $matches);
                $file = '/--';
                print_r(['request' => $requestURL, $matches]);
                if (isset($matches[1]) && isset($matches[2])) {
                    $file = PATH_ROOT . 'App/Projects/' . $matches[1] . '/views' . $matches[2];
                }
                $connection->send($this->withFile($file));
                return true;
            } elseif (str_starts_with($requestURL, '/mstatic/')) {
                preg_match('|/mstatic/([^/]+)(.+)|', $requestURL, $matches);
                $file = '/--';
                if (isset($matches[1]) && isset($matches[2])) {
                    $file = PATH_ROOT . 'App/Modules/' . $matches[1] . '/views' . $matches[2];
                }
                $connection->send($this->withFile($file));
                return true;
            } elseif (preg_match('/^\/(assets|upload|resources|files|bundles)\//', $requestURL)) {
                $file = PATH_PUBLIC . ltrim($requestURL, '/');
                $connection->send($this->withFile($file));
                return true;
            } elseif (preg_match(
                '/\.(js|css|png|jpg|jpeg|gif|ico|svg|woff2|webp|txt|doc|docx|xls|xlsx|ppt|pptx|pdf|sql|phtml)$/',
                $requestURL
            )) {
                $connection->send($this->withFile('//--')); // 404
                return true;
            }
        }
        return false;
    }

    /**
     * @throws Exception
     */
    public function handler(
        \Workerman\Connection\TcpConnection $connection,
        \Workerman\Protocols\Http\Request $request
    ) {
        $requestURL = $request->uri();
        $coroutine = new Coroutine($this->application);
        $pRequest = new Request($request);
        $pResponse = new Response($connection);
        $coroutine->setRequest($pRequest)
            ->setResponse($pResponse)
//            ->setCookie(Cookie::class)
            ->setCookies(ResponseCookies::class, $this->cookiesOptions);

        $sessionOptions = $this->sessionRedisOptions;
        // https://www.workerman.net/doc/workerman/http/session-control.html
        $coroutine->httpDi->setShared('session', function () use ($request, $sessionOptions) {
            Session::handlerClass(RedisSessionHandler::class, $sessionOptions);
            return new SessionManager($request);
        });
        try {
            $coroutine->handler($requestURL)->send();
        } catch (LocationException $e) {
            $connection->send(new \Workerman\Protocols\Http\Response(302, ['Location' => $e->getMessage()]));
        } catch (\Exception $e) {
            if (PRINT_DEBUG_MESSAGE) {
                print_r($e->getTraceAsString());
            }
            $connection->send('inner error in workerman');
        } finally {
            $coroutine->httpDi->close();
        }

        return $requestURL;
    }
}