<?php

namespace Phax\Foundation;

use Phalcon\Di\Di;
use Phalcon\Logger\Exception;
use Phax\Events\Db;
use Phax\Events\Profiler;
use Phax\Support\Config;


class DiService
{
    public static function createDi(): Di
    {
        if (IS_WEB) {
            /**
             * annotations, assets, crypt, cookies, dispatcher,
             * escaper, eventsManager, flash, flashSession, filter,
             * helper, modelsManager, modelsMetadata, request, response,
             * router, security, tag, transactionManager, url,
             */
            return new \Phalcon\Di\FactoryDefault();
        } else {
            /**
             * annotations, dispatcher(CLI), escaper, eventsManager, filter
             * helper, modelsManager, modelsMetadata, router(CLI), security,
             * tag, transactionManager
             */
            return new \Phalcon\Di\FactoryDefault\Cli();
        }
    }

    public function __construct(public Di $di, public bool $shared = true)
    {
    }

    public static function setDi(Di $di, bool $shared = true): DiService
    {
        return new DiService($di, $shared);
    }

    public function getConfig(): Config
    {
        return $this->di->get('config');
    }

    /**
     * @throws Exception
     */
    public function db(): static
    {
        $this->di->set('db', function () {
            $driver = $this->getConfig()->path('database.default');
            $class = 'Phalcon\Db\Adapter\Pdo\\' . $driver;
            $params = $this->getConfig()->path('database.stores.' . $driver)->toArray();
            return new $class($params);
        }, $this->shared);

        $dbLogDriver = $this->getConfig()->path('database.log.driver');
        if ('file' === $dbLogDriver) {
            Db::attach($this->di);
        } elseif ('profiler' === $dbLogDriver) {
            Profiler::attach($this->di);
        }
        return $this;
    }

    public function pdo(): static
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
        }, $this->shared);
        return $this;
    }

    public function redis(): static
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
        }, $this->shared);
        return $this;
    }

    public function cache(): static
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
        }, $this->shared);
        return $this;
    }

    public function cookie(): static
    {
        $this->di->set('cookies', function () {
            $cc = $this->getConfig()->path('cookie');
            if ($cc['key']) {
                $cookie = new \Phalcon\Http\Response\Cookies(true, md5($cc['key']));
            } else {
                $cookie = new \Phalcon\Http\Response\Cookies();
            }

            return $cookie;
        }, $this->shared);
        return $this;
    }

    public function url(): static
    {
        $this->di->set('url', function () {
            $url = new \Phalcon\Mvc\Url();
            $origin = rtrim($this->getConfig()->path('app.url'), '/') . '/';
            $url->setBaseUri($origin);
            return $url;
        }, $this->shared);
        return $this;
    }

    public function flash(): static
    {
        $this->di->set('flash', function () {
            $escaper = new \Phalcon\Html\Escaper();
            $driver = '\Phalcon\Flash\\' . $this->getConfig()->path('flash');
            $flash = new $driver($escaper);
            $flash->setImplicitFlush(false);
            return $flash;
        }, $this->shared);
        return $this;
    }

    public function router(): static
    {
        $this->di->set('router', function () {
            $router = new \Phalcon\Mvc\Router(false);
            $router->removeExtraSlashes(true);
            return $router;
        }, $this->shared);
        return $this;
    }

    public function view(): static
    {
        $this->di->setShared('view', function () {
            $view = new \Phalcon\Mvc\View();
            $view->registerEngines([
                ".phtml" => \Phalcon\Mvc\View\Engine\Php::class,
                '.volt' => 'volt'
            ]);
            return $view;
        });

        $view = $this->di->get('view');
        $di = $this->di;
        $this->di->setShared('volt', function () use ($view, $di) {
            $volt = new \Phalcon\Mvc\View\Engine\Volt($view, $di);

            $volt->setOptions([
//                    'always'    => true,
                'extension' => '.php',
                'separator' => '_',
//                    'stat'      => true,
                'path' => $this->getConfig()->path('view.pathDir'),
//                    'prefix'    => '-prefix-',
            ]);
            return $volt;
        });
        return $this;
    }

    public function application(): static
    {
        if (IS_WEB) {
            $this->di->setShared('application', function () {
                return new \Phalcon\Mvc\Application();
            });
        } else {
            $this->di->setShared('application', function () {
                return new \Phalcon\Cli\Console();
            });
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function session(): static
    {
        /**
         * @link https://docs.phalcon.io/5.0/en/session
         */
        $this->di->set('session', function () {
            $cc = $this->getConfig()->path('session')->toArray();
            // https://stackoverflow.com/questions/8311320/how-to-change-the-session-timeout-in-php
            $sessionConfig = $cc['stores'][$cc['driver']];
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
        }, $this->shared);
        return $this;
    }
}