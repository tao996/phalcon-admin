<?php

use \Phax\Foundation\CliRouter;

// load your self cli script
if (file_exists(__DIR__ . '/cli.more.php')) {
    include_once __DIR__ . '/cli.more.php';
}
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
}, 'run phpunit test');

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
    if (file_exists(PATH_PHAR_SRC . 'phalcon-migrations/index.php')) {
        include_once PATH_PHAR_SRC . 'phalcon-migrations/index.php';
    } elseif (file_exists(PATH_TAO996_PHAR . 'phalcon-migrations.phar')) {
        include_once PATH_TAO996_PHAR . 'phalcon-migrations.phar';
    } else {
        throw new \Exception('phalcon-migrations not found');
    }
    \phalconMigration(function (\Phalcon\Cop\Parser $parser) {
        $argv = empty($_SERVER['argv']) ? [] : $_SERVER['argv'];
        array_shift($argv);
        $parser->parse($argv);
    });
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

CliRouter::add('minify', function ($params) {
    $config = \Phax\Foundation\AppService::config();
    $minify = $config->getArray('app.minify');
    if ($minify) {
        require_once PATH_TAO996_PHAR . 'minify.phar';
        foreach ($minify as $key => $files) {
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
    } else {
        echo '没有需要压缩的 js/css 文件', PHP_EOL;
    }
}, '压缩 js/css 文件');