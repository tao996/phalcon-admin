<?php

namespace App\Console;

use Phalcon\Cli\Task;

class InitTask extends Task
{

    /**
     * php artisan init/index
     *
     * 初始化 src 下的 .env, phpunit.xml
     * @return void
     */
    public function indexAction(): void
    {
        foreach (['.env.example' => '.env', 'phpunit.example.xml' => 'phpunit.xml', 'config/config-dev.example.php' => 'config/config.php'] as $exampleFileName => $toFileName) {
            if (file_exists(PATH_ROOT . $toFileName)) {
                echo 'file ' . $toFileName . ' exists, skip' . PHP_EOL;
                continue;
            }
            copy(PATH_ROOT . $exampleFileName, PATH_ROOT . $toFileName);
            echo 'copy ' . $exampleFileName . ' to ' . $toFileName . ' success' . PHP_EOL;
        }
    }

    /**
     * 初始化 Docker 运行环境
     * php artisan init/docker
     * @return void
     */
    public function dockerAction(): void
    {
        $pathProject = dirname(PATH_ROOT) . DIRECTORY_SEPARATOR;
        foreach (['.env.example' => '.env', 'docker-compose.example.yaml' => 'docker-compose.yaml']
                 as $exampleFileName => $toFileName) {

            if (file_exists($pathProject . $toFileName)) {
                echo 'file ' . $toFileName . ' exists, skip' . PHP_EOL;
                continue;
            }
            copy($pathProject . $exampleFileName, $pathProject . $toFileName);
            echo 'copy ' . $exampleFileName . ' to ' . $toFileName . ' success' . PHP_EOL;
        }
    }
}