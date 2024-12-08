<?php

namespace Phax\Foundation;

use Phalcon\Di\Di;
use Phalcon\Di\DiInterface;
use Phalcon\Logger\Exception;
use Phax\Events\Db;
use Phax\Events\Profiler;
use Phax\Support\Config;

/**
 * 注册 DI 服务
 */
class DiService
{
    protected static DiInterface|null $defaultContainer = null;

    public static function defaultContainer(): Di
    {
        if (null === self::$defaultContainer) {
            // config, db, pdo, redis, cache, application
            // assets, crypt,
            if (IS_WEB) {
                /**
                 * cookies, flash, flashSession,
                 * request, response, router+url(router)
                 * + view
                 * ---------
                 * annotations, dispatcher,escaper, eventsManager,filter,
                 * helper, modelsManager, modelsMetadata, security,
                 * tag, transactionManager,
                 */
                self::$defaultContainer = new \Phalcon\Di\FactoryDefault();
            } else {
                /**
                 * annotations, dispatcher(CLI), escaper, eventsManager, filter
                 * helper, modelsManager, modelsMetadata, router(CLI), security,
                 * tag, transactionManager
                 */
                self::$defaultContainer = new \Phalcon\Di\FactoryDefault\Cli();
            }
        }
        return self::$defaultContainer;
    }

    public function __construct(public Di $di)
    {
    }

    public static function with(Di $di): DiService
    {
        return new DiService($di);
    }

    /**
     * 读取配置信息
     * @param callable{\Phalcon\Config\Config} $handle 通常用于初始化数据
     * @param bool $shared
     * @return $this
     */
    public function config(callable $handle, bool $shared = true): static
    {
        // 配置文件
        $cc = new Config($this->di);
        $this->di->set('config', $cc, $shared);
        $handle($cc->load());
        return $this;
    }

    public function getConfig(): Config
    {
        return $this->di->get('config');
    }

    public function logger(bool $shared = true): static
    {
        $this->di->set('logger', function () {
            $cc = $this->getConfig();
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
        }, $shared);
        return $this;
    }

    public function crypt(bool $shared = true): static
    {
        // logger
        // 注册加密
        $this->di->set('crypt', function () {
            $cc = $this->getConfig();
            $data = $cc->path('crypt')->toArray();

            $crypt = new \Phalcon\Encryption\Crypt();
            if ($data['key']) {
                $crypt->setKey($data['key']);
            }
            if ($data['padding']) {
                $crypt->setPadding($data['padding']);
            }
            $crypt->setCipher($data['cipher']);
            return $crypt;
        }, $shared);
        return $this;
    }

    public function modelsMetadata(bool $shared = true): static
    {
        // https://docs.phalcon.io/5.0/en/db-models-metadata
        $this->di->set('modelsMetadata', function () {
            $cc = $this->getConfig();
            $data = $cc->path('metadata')->toArray();
            switch ($data['driver']) {
                case 'apcu':
                    $factory = new \Phalcon\Storage\SerializerFactory();
                    $adapter = new \Phalcon\Cache\AdapterFactory($factory);
                    return new \Phalcon\Mvc\Model\MetaData\Apcu($adapter, $data['stores']['apcu']);
                case 'memcached':
                    $factory = new \Phalcon\Storage\SerializerFactory();
                    $adapter = new \Phalcon\Cache\AdapterFactory($factory);
                    return new \Phalcon\Mvc\Model\MetaData\Libmemcached($adapter, $data['stores']['memcached']);
                case 'redis':
                    $factory = new \Phalcon\Storage\SerializerFactory();
                    $adapter = new \Phalcon\Cache\AdapterFactory($factory);
                    return new \Phalcon\Mvc\Model\MetaData\Redis($adapter, $data['stores']['redis']);
                case 'stream':
                    return new \Phalcon\Mvc\Model\MetaData\Stream($data['stores']['stream']);
                default:
                    return new \Phalcon\Mvc\Model\Metadata\Memory();
            }
        }, $shared);
        return $this;
    }

    public function profiler(bool $shared = true): static
    {
        $this->di->set('profiler', function () {
            return new \Phalcon\Db\Profiler();
        }, $shared);
        return $this;
    }

    public function security(bool $shared = true): static
    {
        $this->di->set('security', function () {
            return new \Phalcon\Encryption\Security();
        }, $shared);
        return $this;
    }

    /**
     * @throws Exception
     */
    public function db(bool $shared = true): static
    {
        $di = $this->di;
        $this->di->set('db', function () use ($di) {
            $driver = $this->getConfig()->path('database.default');
            $class = 'Phalcon\Db\Adapter\Pdo\\' . $driver;
            $params = $this->getConfig()->path('database.stores.' . $driver)->toArray();
            $db = new $class($params);

            $dbLogDriver = $this->getConfig()->path('database.log.driver');
            if ('file' === $dbLogDriver) {
                Db::attach($di, $db);
            } elseif ('profiler' === $dbLogDriver) {
                Profiler::attach($di, $db);
            }
            return $db;
        }, $shared);

        return $this;
    }

    public function pdo(bool $shared = true): static
    {
        $this->di->set('pdo', function () {
            $driver = $this->getConfig()->path('database.default');
            $params = $this->getConfig()->path('database.stores.' . $driver)->toArray();

            switch ($driver) {
                case 'mysql':
                    $dsn = 'mysql:host=' . $params['host'] . ';port=' . $params['port'] . ';dbname=' . $params['dbname'] . ';charset=' . $params['charset'];

                    $pdo = new \PDO($dsn, $params['username'], $params['password'], [
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
                    ]);
                    // 不要将 int 字段转为 string
                    $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
                    $pdo->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);

                    // 在非生产环境下，取消预处理功能，性能下降，但可以看到最终的 sql 语句
                    // 或者你可以通过函数  getRawPdoSql 来打印预处理的语句
                    if (is_debug()) {
                        $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
                    }
                    return $pdo;
                case 'postgresql':
                    $dsn = "pgsql:host={$params['host']};port={$params['port']};dbname={$params['dbname']};user={$params['username']};password={$params['password']}";
                    $pdo = new \PDO($dsn);
                    $pdo->exec("set names utf8");
                    return $pdo;
                case 'sqlite':
                    $dsn = "sqlite:{$params['dbname']}";
                    return new \PDO(
                        $dsn, null, null,
                        array(\PDO::ATTR_PERSISTENT => true)
                    );
            }
            throw new \Exception('PDO Service wat not create in Di');
        }, $shared);
        return $this;
    }

    public function redis(bool $shared = true): static
    {
        // redis
        $this->di->set('redis', function () {
            $cc = $this->getConfig()->path('redis')->toArray();
            $redis = new \Redis();
            $redis->connect($cc['host'], $cc['port']);
            if (!empty($cc['auth'])) {
                $redis->auth($cc['auth']);
            }
            $redis->select($cc['index']);
            $redis->persist($cc['persistent']);
            $redis->_prefix($cc['prefix']);
            return $redis;
        }, $shared);
        return $this;
    }

    public function cache(bool $shared = true): static
    {
        // https://docs.phalcon.io/5.0/en/cache
        $this->di->set('cache', function () {
            $factory = new \Phalcon\Storage\SerializerFactory();
            $cc = $this->getConfig()->path('cache')->toArray();
            $options = $cc['stores'][$cc['driver']];
            switch ($cc['driver']) {
                case 'redis':
                    $adapter = new \Phalcon\Cache\Adapter\Redis($factory, $options);
                    break;
                case 'stream':
                    $adapter = new \Phalcon\Cache\Adapter\Stream($factory, $options);
                    break;
                case 'memory':
                    $adapter = new \Phalcon\Cache\Adapter\Memory($factory, $options);
                    break;
                case 'memcached':
                    $adapter = new \Phalcon\Cache\Adapter\Libmemcached($factory, $options);
                    break;
                case 'apcu':
                    $adapter = new \Phalcon\Cache\Adapter\Apcu($factory, $options);
                    break;
                default:
                    $adapterFactory = new \Phalcon\Cache\AdapterFactory($factory);
                    $adapter = $adapterFactory->newInstance($cc['driver'], $options);
            }
            return new \Phalcon\Cache\Cache($adapter);
        }, $shared);
        return $this;
    }

    public function cookies(bool $shared = true): static
    {
        $this->di->set('cookies', function () {
            $cc = $this->getConfig()->path('cookie');
            if ($cc['key']) {
                $cookie = new \Phalcon\Http\Response\Cookies(true, md5($cc['key']));
            } else {
                $cookie = new \Phalcon\Http\Response\Cookies();
            }

            return $cookie;
        }, $shared);
        return $this;
    }

    public function url(bool $shared = true): static
    {
        $this->di->set('url', function () {
            return new \Phalcon\Mvc\Url();
//            $origin = rtrim($this->getConfig()->path('app.url'), '/') . '/';
//            $url->setBaseUri($origin);
        }, $shared);
        return $this;
    }

    public function flash(bool $shared = true): static
    {
        $this->di->set('flash', function () {
            $escaper = new \Phalcon\Html\Escaper();
            $driver = '\Phalcon\Flash\\' . $this->getConfig()->path('flash');
            $flash = new $driver($escaper);
            $flash->setImplicitFlush(false);
            return $flash;
        }, $shared);
        return $this;
    }

    public function router(bool $shared = true): static
    {
        $this->di->set('router', function () {
            $router = new \Phalcon\Mvc\Router(false);
            $router->removeExtraSlashes(true);
            return $router;
        }, $shared);
        return $this;
    }

    public function view(bool $share = true): static
    {
        $di = $this->di;
        $this->di->set('view', function () use ($di, $share) {
            $view = new \Phalcon\Mvc\View();
            $view->registerEngines([
                ".phtml" => \Phalcon\Mvc\View\Engine\Php::class,
                '.volt' => 'volt'
            ]);
            return $view;
        }, $share);

        $di->set('volt', function () use ($di) {
            $volt = new \Phalcon\Mvc\View\Engine\Volt($di->get('view'), $di);

            $volt->setOptions([
//                    'always'    => true,
                'extension' => '.php',
                'separator' => '_',
//                    'stat'      => true,
                'path' => $this->getConfig()->path('view.pathDir'),
//                    'prefix'    => '-prefix-',
            ]);
            return $volt;
        }, $share);
        return $this;
    }

    public function application(bool $shared = true): static
    {
        if (IS_WEB) {
            $this->di->set('application', function () {
                return new \Phalcon\Mvc\Application();
            }, $shared);
        } else {
            $this->di->set('application', function () {
                return new \Phalcon\Cli\Console();
            }, $shared);
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function session(bool $shared = true): static
    {
        /**
         * @link https://docs.phalcon.io/5.0/en/session
         */
        $this->di->set('session', function () {
            $cc = $this->getConfig()->path('session')->toArray();
            // https://stackoverflow.com/questions/8311320/how-to-change-the-session-timeout-in-php
            $sessionConfig = $cc['stores'][$cc['driver']];
//            ddd($sessionConfig);
            switch ($cc['driver']) {
                case 'stream':
                    $adapter = new \Phalcon\Session\Adapter\Stream($sessionConfig);
                    break;
                case 'memcached':
                    $serializerFactory = new \Phalcon\Storage\SerializerFactory();
                    $factory = new \Phalcon\Storage\AdapterFactory($serializerFactory);
                    $adapter = new \Phalcon\Session\Adapter\Libmemcached($factory, $sessionConfig);
                    break;
                case 'redis':

                    $serializerFactory = new \Phalcon\Storage\SerializerFactory();
                    $factory = new \Phalcon\Storage\AdapterFactory($serializerFactory);
                    $adapter = new \Phalcon\Session\Adapter\Redis($factory, $sessionConfig);
//                    $adapter = new MyPHPSer($factory, $sessionConfig);
                    break;
                case 'noop':
                    $adapter = new \Phalcon\Session\Adapter\Noop();
                    break;
                default:
                    throw new \Exception('un support session driver');
            }
            $session = new \Phalcon\Session\Manager();
            $session->setAdapter($adapter);
            $session->start();
            return $session;
        }, $shared);
        return $this;
    }
}