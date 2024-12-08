<?php

namespace Phax\Foundation;

class CliRouter
{

    private static array $cmd = [];

    public static function find(): array
    {
        $rows = [];
        foreach (self::$cmd as $name => $info) {
            $rows[$name] = $info[1];
        }
        return $rows;
    }

    /**
     * 添加命令
     * @param string $name 名称
     * @param string|callable $action 所执行的命令或回调函数
     * @param string $desc
     * @return void
     */
    public static function add(string $name, string|callable $action, string $desc): void
    {
        if (isset(self::$cmd[$name])) {
            die('repeat CLI:' . $name);
        }
        self::$cmd[$name] = [$action, $desc];
    }

    /**
     * @param string $name 待执行命令的名称
     * @return bool
     */
    public static function runWith(string $name): bool
    {
        global $argv;
        if (isset(self::$cmd[$name])) {
            $params = array_slice($argv, 2);
            $info = self::$cmd[$name];
            if (is_callable($info[0])) {
                $info[0]($params); // 调用函数
            } else {
                system($info[0] . ' ' . join(' ', $params), $code);
            }
            return true;
        }
        return false;
    }

    /**
     * @param string $route main/test , m/demo/main/test, p/demo/main/test 不支持子目录子模块
     * @param array $options ['m'=>'模块名称','p'=>'项目前缀']
     * @return array
     * @throws \Exception
     */
    public static function handle(string $route, array $options = []): array
    {
        $options = array_merge(['m' => 'm', 'p' => 'p'], $options);

        $arguments = [
            'task' => 'main',
            'action' => 'index',
            'namespace' => 'App\Console'
        ];
        $route = ltrim($route, '/');
        $items = explode('/', $route);

        if ($items[0] == $options['m']) { // 多模块
            if (empty($items[1])) {
                throw new \Exception('必须指定 Module 名称');
            }

            $arguments['module'] = $items[1];
            $path = PATH_APP_MODULES . $items[1] . '/Module.php';
            $hasModule = file_exists($path);

            $arguments['modules'] = [
                $items[1] => [
                    'path' => $hasModule ? $path
                        : dirname(__DIR__) . '/Mvc/Module.php',
                    'className' => $hasModule
                        ? 'App\Modules\\' . $items[1] . '\Module'
                        : 'Phax\Mvc\Module',

                ]
            ];

            $arguments['namespace'] = 'App\Modules\\' . $items[1] . '\Console';
            $arguments['task'] = $items[2] ?? 'main';
            $arguments['action'] = $items[3] ?? 'index';
        } elseif ($items[0] == $options['p']) {
            if (empty($items[1])) {
                throw new \Exception('必须指定 Project 名称');
            }

            $arguments['namespace'] = 'App\Projects\\' . $items[1] . '\Console';
            $arguments['task'] = $items[2] ?? 'main';
            $arguments['action'] = $items[3] ?? 'index';
        } else {
            if (!empty($items[0])) {
                $arguments['task'] = $items[0];
            }
            if (!empty($items[1])) {
                $arguments['action'] = $items[1];
            }
        }

        return $arguments;
    }
}