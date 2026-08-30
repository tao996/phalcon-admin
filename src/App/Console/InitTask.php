<?php

namespace App\Console;

use Phalcon\Cli\Task;

class InitTask extends Task
{
    /**
     * 初始化环境
     * @return void
     */
    public function indexAction()
    {
        // 源码目录
        $pathProject = dir(PATH_ROOT) . DIRECTORY_SEPARATOR;
        foreach (['.env.example', 'docker-compose.example.yaml'] as $exampleFile) {
            $toFileName = str_replace('.example', '', $exampleFile);
            if (file_exists($pathProject . $toFileName)) {
                continue;
            }
            copy($pathProject . $exampleFile, $pathProject . $toFileName);
            echo 'copy ' . $exampleFile . ' to ' . $toFileName . ' success' . PHP_EOL;
        }
    }
}