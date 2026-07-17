<?php

declare(strict_types=1);

namespace Tests\Unit\tao996\phax\Support;

use Phax\Foundation\CliRouter;
use Phax\Foundation\Context\RouteContext;
use Phax\Foundation\Route;
use Phax\Support\Router;


class RouterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * 创建测试用 RouteContext
     * @param string $project 项目名称（可选）
     */
    private function dto(string $project = ''): RouteContext
    {
        $dto = new RouteContext();
        $dto->defaultNamespace = 'App\Http\Controllers';
        $dto->defaultViewPath = PATH_APP . 'Http' . DIRECTORY_SEPARATOR . 'views';
        if ($project) {
            $dto->projectName = $project;
            $dto->projectNamespace = 'App\Projects\\' . $project . '\Controllers';
            $dto->projectViewPath = PATH_APP_PROJECTS . $project . DIRECTORY_SEPARATOR . 'views';
        }
        return $dto;
    }

    public function testPathPattern()
    {
//        ddd(Router::pathMatch('/cn/m/aa.bb'));
        $testDatas = [
            [
                '/',
                [
                    'language' => '',
                    'api' => false,
                    'project' => false,
                    'module' => false,
                    'path' => '',
                    'mapurl' => '/'
                ]
            ],

            [
                '/zh-CN/',
                [
                    'language' => 'zh-CN',
                    'api' => false,
                    'project' => false,
                    'module' => false,
                    'path' => '',
                    'mapurl' => '/zh-CN/'
                ]
            ],
            [
                '/p/',
                [
                    'language' => '',
                    'api' => false,
                    'project' => true,
                    'module' => false,
                    'path' => '',
                    'mapurl' => '/p/'
                ]
            ],
            [
                '/p/bb',
                [
                    'language' => '',
                    'api' => false,
                    'project' => true,
                    'module' => false,
                    'path' => 'bb',
                    'mapurl' => '/p/bb'
                ]
            ],
            [
                '/p/bb/',
                [
                    'language' => '',
                    'api' => false,
                    'project' => true,
                    'module' => false,
                    'path' => 'bb',
                    'mapurl' => '/p/bb/'
                ]
            ],
            [
                '/p/bb/index',
                [
                    'language' => '',
                    'api' => false,
                    'project' => true,
                    'module' => false,
                    'path' => 'bb/index',
                    'mapurl' => '/p/bb/index'
                ]
            ],
            [
                '/m/',
                [
                    'language' => '',
                    'api' => false,
                    'project' => false,
                    'module' => true,
                    'path' => '',
                    'mapurl' => '/m/'
                ]
            ],
            [
                '/m/aa',
                [
                    'language' => '',
                    'api' => false,
                    'project' => false,
                    'module' => true,
                    'path' => 'aa',
                    'mapurl' => '/m/aa'
                ]
            ],
            [
                '/cn/m/aa',
                [
                    'language' => 'cn',
                    'api' => false,
                    'project' => false,
                    'module' => true,
                    'path' => 'aa',
                    'mapurl' => '/cn/m/aa'
                ]
            ],
            [
                '/m/aa/',
                [
                    'language' => '',
                    'api' => false,
                    'project' => false,
                    'module' => true,
                    'path' => 'aa',
                    'mapurl' => '/m/aa/'
                ]
            ],
            [
                '/m/aa/index',
                [
                    'language' => '',
                    'api' => false,
                    'project' => false,
                    'module' => true,
                    'path' => 'aa/index',
                    'mapurl' => '/m/aa/index'
                ]
            ],
            [
                '/zh-CN/api/p/project/controller/action/params',
                [
                    'language' => 'zh-CN',
                    'api' => true,
                    'project' => true,
                    'module' => false,
                    'path' => 'project/controller/action/params',
                    'mapurl' => '/zh-CN/api/p/project/controller/action/params'
                ]
            ]
        ];
        foreach ($testDatas as $testData) {
            $this->assertEquals($testData[1], Router::pathMatch($testData[0]), 'not equal:' . $testData[0]);
        }

//        $matches = Router::pathMatch('/'.Router::$cliKeyword.'/');
//        ddd($matches);
    }

    /**
     * @throws \Exception
     */
    public function testErrorUse()
    {
//        $rst = Router::analysisRoutePath('/m/demo.db/test/list', $this->dto());
//        ddd($rst);
        $rst = Router::analysisRoutePath('/api/p/family/vip/notify/wx964c9beb6dc7131b', $this->dto('family'));
//        ddd($rst);
        $this->assertEquals([
            "pattern" => "/api/p/family/:controller/:action/:params",
            "paths" => [
                "controller" => 1,
                "action" => 2,
                "params" => 3,
            ],
            "pathsname" => [
                "controller" => "vip",
                "action" => "notify",
            ],
            "namespace" => "App\Projects\\family\Controllers",
            "viewpath" => PATH_APP_PROJECTS . "family" . DIRECTORY_SEPARATOR . "views",
            "project" => "family",
        ], $rst);
    }

    public function testUrl()
    {
        $rst1 = \Phax\Support\Router::analysisWithURL('/m/tao', $this->dto());
        $this->assertEquals('/m/:module', $rst1['route']);
        $this->assertEquals('index', $rst1['paths']['controller']);
        $this->assertEquals('index', $rst1['paths']['action']);

        $rst2 = \Phax\Support\Router::analysisWithURL('/m/tao/', $this->dto());
        $this->assertEquals('/m/:module/', $rst2['route']);

//        $route = new Route('/m/tao', \Phax\Foundation\Application::di());
//        $route->routerOptions = $rst2;
//        ddd($route->routerOptions);
    }

    public function testApp()
    {
        $route = new Route('', \Phax\Foundation\Application::di());
        // 默认应用,路由设计
        $baseInfo = [
            'namespace' => "App\Http\Controllers",
            'viewpath' => PATH_APP . "Http" . DIRECTORY_SEPARATOR . "views",
        ];
        $expect = [
            'pattern' => '/',
            'paths' => ['controller' => 'index', 'action' => 'index'],
            'pathsname' => ['controller' => 'index', 'action' => 'index'],
            ...$baseInfo,
            'project' => ''
        ];
        $rst = Router::analysisRoutePath('', $this->dto());
        $this->assertEquals($expect, $rst);
        $node = $route->getNode($rst);
        $this->assertEquals('index/index', $node);

        $rst = Router::analysisRoutePath('/', $this->dto());
        $this->assertEquals($expect, $rst);
        $node = $route->getNode($rst);
        $this->assertEquals('index/index', $node);

        $rst1 = Router::analysisRoutePath('/cn/', $this->dto());
        $expect['pattern'] = Router::$languageRule . $expect['pattern'];
        $this->assertEquals($expect, $rst1);


        $rst = Router::analysisRoutePath('/c1', $this->dto());
        $this->assertEquals([
            'pattern' => '/:controller',
            'paths' => ['controller' => 1, 'action' => 'index'],
            'pathsname' => ['controller' => 'c1', 'action' => 'index'],
            'project' => '',
            ...$baseInfo
        ], $rst);
        $node = $route->getNode($rst);
        $this->assertEquals('c1/index', $node);

        $rst = Router::analysisRoutePath('/c2/a2', $this->dto());
        $this->assertEquals([
            'pattern' => '/:controller/:action',
            'paths' => ['controller' => 1, 'action' => 2],
            'pathsname' => ['controller' => 'c2', 'action' => 'a2'],
            'project' => '',
            ...$baseInfo
        ], $rst);
        $node = $route->getNode($rst);
        $this->assertEquals('c2/a2', $node);

        $rst = Router::analysisRoutePath('/c2/a2/p', $this->dto());
        $this->assertEquals([
            'pattern' => '/:controller/:action/:params',
            'paths' => ['controller' => 1, 'action' => 2, 'params' => 3],
            'pathsname' => ['controller' => 'c2', 'action' => 'a2'],
            'project' => '',
            ...$baseInfo
        ], $rst);
        $node = $route->getNode($rst);
        $this->assertEquals('c2/a2', $node);

        $rst = Router::analysisRoutePath('/c2/a2/p1/p2', $this->dto());
        $this->assertEquals([
            'pattern' => '/:controller/:action/:params',
            'paths' => ['controller' => 1, 'action' => 2, 'params' => 3],
            'pathsname' => ['controller' => 'c2', 'action' => 'a2'],
            'project' => '',
            ...$baseInfo
        ], $rst);
        $node = $route->getNode($rst);
        $this->assertEquals('c2/a2', $node);

        $rst = Router::analysisRoutePath('/c2/a2/p1/p2/p3', $this->dto());
        $this->assertEquals([
            'pattern' => '/:controller/:action/:params',
            'paths' => ['controller' => 1, 'action' => 2, 'params' => 3],
            'pathsname' => ['controller' => 'c2', 'action' => 'a2'],
            'project' => '',
            ...$baseInfo
        ], $rst);
        $node = $route->getNode($rst);
        $this->assertEquals('c2/a2', $node);

        // 普通 c1/a1/p1，单应用没有子模块（module/controller/action）因为跟普通路径冲突，并且文件目录没得放

        // 单应用子目录 sub.c1 或者 sub.c1/a1 或者 sub.c1/a1/p1
        $rst = Router::analysisRoutePath('/sub.c1', $this->dto());
        $this->assertEquals([
            'pattern' => '/:controller',
            'paths' => ['controller' => 1, 'action' => 'index'],
            'pathsname' => ['controller' => 'c1', 'action' => 'index'],
            'namespace' => "App\Http\Controllers\sub",
            'viewpath' => PATH_APP . "Http" . DIRECTORY_SEPARATOR . "views",
            'subc' => 'sub',
            'project' => '',
        ], $rst);
        $node = $route->getNode($rst);
        $this->assertEquals('sub.c1/index', $node);

        $rst = Router::analysisRoutePath('/sub.c2/a2', $this->dto());
        $this->assertEquals([
            'pattern' => '/:controller/:action',
            'paths' => ['controller' => 1, 'action' => 2],
            'pathsname' => ['controller' => 'c2', 'action' => 'a2'],
            'namespace' => "App\Http\Controllers\sub",
            'viewpath' => PATH_APP . "Http" . DIRECTORY_SEPARATOR . "views",
            'subc' => 'sub',
            'project' => '',
        ], $rst);
        $node = $route->getNode($rst);
        $this->assertEquals('sub.c2/a2', $node);

        $rst = Router::analysisRoutePath('/sub.c2/a2/p1', $this->dto());
        $this->assertEquals([
            'pattern' => '/:controller/:action/:params',
            'paths' => ['controller' => 1, 'action' => 2, 'params' => 3],
            'pathsname' => ['controller' => 'c2', 'action' => 'a2'],
            'namespace' => "App\Http\Controllers\sub",
            'viewpath' => PATH_APP . "Http" . DIRECTORY_SEPARATOR . "views",
            'subc' => 'sub','project' => '',
        ], $rst);
        $node = $route->getNode($rst);
        $this->assertEquals('sub.c2/a2', $node);

        $rst = Router::analysisRoutePath('/sub.c2/a2/p1/p2', $this->dto());
        $this->assertEquals([
            'pattern' => '/:controller/:action/:params',
            'paths' => ['controller' => 1, 'action' => 2, 'params' => 3],
            'pathsname' => ['controller' => 'c2', 'action' => 'a2'],
            'namespace' => "App\Http\Controllers\sub",
            'viewpath' => PATH_APP . "Http" . DIRECTORY_SEPARATOR . "views",
            'subc' => 'sub','project' => '',
        ], $rst);
        $node = $route->getNode($rst);
        $this->assertEquals('sub.c2/a2', $node);
    }

    public function testModule()
    {
        $route = new Route('', \Phax\Foundation\Application::di());
        // 多模块
        // 多模块默认全部放在 app/Modules 目录下，子模块放在 app/Modules/多模块/A0/子模块 目录下
        $expect = [
            'pattern' => '/m/',
            'paths' => ['module' => 'index', 'controller' => 'index', 'action' => 'index'],
            'pathsname' => ['module' => 'index', 'controller' => 'index', 'action' => 'index'],
            'namespace' => "App\Modules\index\Controllers",
            'viewpath' => PATH_APP_MODULES . 'index' . DIRECTORY_SEPARATOR . 'views',
            'module' => PATH_APP_MODULES . 'index' . DIRECTORY_SEPARATOR . 'Module.php',
            'name' => 'index',
        ];

        $rst = Router::analysisRoutePath('/m/', $this->dto());
        $this->assertEquals($expect, $rst);

        $rst = Router::analysisRoutePath('/cn/m/', $this->dto());
        $expect['pattern'] = Router::$languageRule . $expect['pattern'];
        $this->assertEquals($expect, $rst);


        $node = $route->getNode($rst);
        $this->assertEquals('index/index/index', $node);

        $expect = [
            'pattern' => '/m/:module',
            'paths' => ['module' => 1, 'controller' => 'index', 'action' => 'index'],
            'pathsname' => ['module' => 'm1', 'controller' => 'index', 'action' => 'index'],
            'namespace' => "App\Modules\m1\Controllers",
            'viewpath' => PATH_APP_MODULES . "m1" . DIRECTORY_SEPARATOR . "views",
            'module' => PATH_APP_MODULES . 'm1' . DIRECTORY_SEPARATOR . 'Module.php',
            'name' => 'm1',
        ];
        $rst = Router::analysisRoutePath('/m/m1', $this->dto());
        $this->assertEquals($expect, $rst);

        $rst = Router::analysisRoutePath('/m/M1', $this->dto()); // 大写
        $this->assertEquals($expect, $rst);

        $rst = Router::analysisRoutePath('/en/m/m1', $this->dto());
        $expect['pattern'] = Router::$languageRule . $expect['pattern'];
        $expect['paths']['module'] = 2;
        $this->assertEquals($expect, $rst);

        $node = $route->getNode($rst);
        $this->assertEquals('m1/index/index', $node);

        $expect = [
            'pattern' => '/m/:module/:controller',
            'paths' => ['module' => 1, 'controller' => 2, 'action' => 'index'],
            'pathsname' => ['module' => "m1", 'controller' => "c", 'action' => 'index'],
            'namespace' => "App\Modules\m1\Controllers",
            'viewpath' => PATH_APP_MODULES . "m1" . DIRECTORY_SEPARATOR . "views",
            'module' => PATH_APP_MODULES . 'm1' . DIRECTORY_SEPARATOR . 'Module.php',
            'name' => 'm1',
        ];
        $rst = Router::analysisRoutePath('/m/m1/c', $this->dto());
        $this->assertEquals($expect, $rst);

        $rst = Router::analysisRoutePath('/en/m/m1/c', $this->dto());
        $expect['pattern'] = Router::$languageRule . $expect['pattern'];
        $expect['paths']['module'] = 2;
        $expect['paths']['controller'] = 3;
        $this->assertEquals($expect, $rst);

        $node = $route->getNode($rst);
        $this->assertEquals('m1/c/index', $node);

        $expect = [
            'pattern' => '/m/:module/:controller/:action',
            'paths' => ['module' => 1, 'controller' => 2, 'action' => 3],
            'pathsname' => ['module' => 'm1', 'controller' => 'c1', 'action' => 'a1'],
            'namespace' => "App\Modules\m1\Controllers",
            'viewpath' => PATH_APP_MODULES . "m1" . DIRECTORY_SEPARATOR . "views",
            'module' => PATH_APP_MODULES . 'm1' . DIRECTORY_SEPARATOR . 'Module.php',
            'name' => 'm1',
        ];
        $rst = Router::analysisRoutePath('/m/m1/c1/a1', $this->dto());
        $this->assertEquals($expect, $rst);
        $rst = Router::analysisRoutePath('/en/m/m1/c1/a1', $this->dto());
        $expect['pattern'] = Router::$languageRule . $expect['pattern'];
        $expect['paths']['module'] = 2;
        $expect['paths']['controller'] = 3;
        $expect['paths']['action'] = 4;
        $this->assertEquals($expect, $rst);

        $node = $route->getNode($rst);
        $this->assertEquals('m1/c1/a1', $node);


        $expect = [
            'pattern' => '/m/:module/:controller/:action/:params',
            'paths' => ['module' => 1, 'controller' => 2, 'action' => 3, 'params' => 4],
            'pathsname' => ['module' => 'm2', 'controller' => 'c2', 'action' => 'a2'],
            'namespace' => "App\Modules\m2\Controllers",
            'viewpath' => PATH_APP_MODULES . "m2" . DIRECTORY_SEPARATOR . "views",
            'module' => PATH_APP_MODULES . 'm2' . DIRECTORY_SEPARATOR . 'Module.php',
            'name' => 'm2',
        ];
        $rst = Router::analysisRoutePath('/m/m2/c2/a2/p', $this->dto());
        $this->assertEquals($expect, $rst);

        $rst = Router::analysisRoutePath('/en/m/m2/c2/a2/p1/p2/p3', $this->dto());
        $expect['pattern'] = Router::$languageRule . $expect['pattern'];
        $expect['paths']['module'] = 2;
        $expect['paths']['controller'] = 3;
        $expect['paths']['action'] = 4;
        $expect['paths']['params'] = 5;
        $this->assertEquals($expect, $rst);

        $node = $route->getNode($rst);
        $this->assertEquals('m2/c2/a2', $node);

        // 多模块：子模块
        $expect = [
            'pattern' => '/m/:module',
            'pathsname' => ['module' => 'tao', 'controller' => 'index', 'action' => 'index'],
            'namespace' => 'App\Modules\tao\A0\wechat\Controllers',
            'viewpath' => PATH_APP_MODULES . 'tao' . DIRECTORY_SEPARATOR . 'A0' . DIRECTORY_SEPARATOR . 'wechat' . DIRECTORY_SEPARATOR . 'views',
            'route' => '/m/:module\.wechat',
        ];
        $keys = ['pattern', 'pathsname', 'namespace', 'viewpath', 'route'];
        $rst = Router::analysisWithURL('/m/tao.wechat', $this->dto());
        $this->assertEquals($expect, \Phax\Utils\MyData::getByKeys($rst, $keys));

        $rst = Router::analysisWithURL('/cn/m/tao.wechat', $this->dto());
        $expect['pattern'] = Router::$languageRule . $expect['pattern'];
        $expect['route'] = Router::$languageRule . $expect['route'];
        $this->assertEquals($expect, \Phax\Utils\MyData::getByKeys($rst, $keys));


        $rst = Router::analysisRoutePath('/m/m1.m11/c1', $this->dto());
        $this->assertEquals([
            'pattern' => '/m/:module/:controller',
            'paths' => ['module' => 1, 'controller' => 2, 'action' => 'index'],
            'pathsname' => ['module' => 'm1', 'controller' => 'c1', 'action' => 'index'],
            'namespace' => "App\Modules\m1\A0\\m11\Controllers",
            'viewpath' => PATH_APP_MODULES . "m1" . DIRECTORY_SEPARATOR . "A0" . DIRECTORY_SEPARATOR . "m11" . DIRECTORY_SEPARATOR . "views",
            'module' => PATH_APP_MODULES . 'm1' . DIRECTORY_SEPARATOR . 'Module.php',
            'name' => 'm1',
            'subm' => 'm11',
        ], $rst);

        $rst = Router::analysisWithURL('/m/m1.m11/c1', $this->dto());
        $this->assertEquals([
            'registerModules' => [
                'm1' => [
                    'path' => PATH_TAO996_PHAX . 'Mvc' . DIRECTORY_SEPARATOR . 'Module.php',
                    'className' => 'Phax\Mvc\Module'
                ]
            ],
            'route' => '/m/:module\.m11/:controller'
        ], [
            'registerModules' => $rst['registerModules'],
            'route' => $rst['route']
        ]);


        $node = $route->getNode($rst);
        $this->assertEquals('m1.m11/c1/index', $node);

        // 多模块：子模块+子目录
        $rst = Router::analysisRoutePath('/m/m1.m11/sub1.c2', $this->dto());
        $this->assertEquals([
            'pattern' => '/m/:module/:controller',
            'paths' => ['module' => 1, 'controller' => 2, 'action' => 'index'],
            'pathsname' => ['module' => 'm1', 'controller' => 'c2', 'action' => 'index'],
            'namespace' => "App\Modules\m1\A0\\m11\Controllers\sub1",
            'viewpath' => PATH_APP_MODULES . "m1" . DIRECTORY_SEPARATOR . "A0" . DIRECTORY_SEPARATOR . "m11" . DIRECTORY_SEPARATOR . "views",
            'module' => PATH_APP_MODULES . 'm1' . DIRECTORY_SEPARATOR . 'Module.php',
            'name' => 'm1',
            'subc' => 'sub1',
            'subm' => 'm11',
        ], $rst);

        $node = $route->getNode($rst);
        $this->assertEquals('m1.m11/sub1.c2/index', $node);

        // 多模块子目录
        $rst = Router::analysisRoutePath('/m/m1/sub1.c1', $this->dto());
        $this->assertEquals([
            'pattern' => '/m/:module/:controller',
            'paths' => ['module' => 1, 'controller' => 2, 'action' => 'index'],
            'pathsname' => ['module' => 'm1', 'controller' => 'c1', 'action' => 'index'],
            'namespace' => "App\Modules\m1\Controllers\sub1",
            'viewpath' => PATH_APP_MODULES . "m1" . DIRECTORY_SEPARATOR . "views",
            'module' => PATH_APP_MODULES . 'm1' . DIRECTORY_SEPARATOR . 'Module.php',
            'name' => 'm1',
            'subc' => 'sub1',
        ], $rst);

        $node = $route->getNode($rst);
        $this->assertEquals('m1/sub1.c1/index', $node);

        $rst = Router::analysisRoutePath('/m/m1/sub.c1/a1', $this->dto());
        $this->assertEquals([
            'pattern' => '/m/:module/:controller/:action',
            'paths' => ['module' => 1, 'controller' => 2, 'action' => 3],
            'pathsname' => ['module' => 'm1', 'controller' => 'c1', 'action' => 'a1'],
            'namespace' => "App\Modules\m1\Controllers\sub",
            'viewpath' => PATH_APP_MODULES . "m1" . DIRECTORY_SEPARATOR . "views",
            'module' => PATH_APP_MODULES . 'm1' . DIRECTORY_SEPARATOR . 'Module.php',
            'name' => 'm1',
            'subc' => 'sub',
        ], $rst);

        $node = $route->getNode($rst);
        $this->assertEquals('m1/sub.c1/a1', $node);

        $rst = Router::analysisRoutePath('/m/m1/sub.c1/a1/p1', $this->dto());
        $this->assertEquals([
            'pattern' => '/m/:module/:controller/:action/:params',
            'paths' => ['module' => 1, 'controller' => 2, 'action' => 3, 'params' => 4],
            'pathsname' => ['module' => 'm1', 'controller' => 'c1', 'action' => 'a1'],
            'namespace' => "App\Modules\m1\Controllers\sub",
            'viewpath' => PATH_APP_MODULES . "m1" . DIRECTORY_SEPARATOR . "views",
            'module' => PATH_APP_MODULES . 'm1' . DIRECTORY_SEPARATOR . 'Module.php',
            'name' => 'm1',
            'subc' => 'sub',
        ], $rst);
        $node = $route->getNode($rst);
        $this->assertEquals('m1/sub.c1/a1', $node);

    }

    /**
     * @throws \Exception
     */
    public function testSubApp()
    {
        $route = new Route('', \Phax\Foundation\Application::di());

        // 单应用子模块+子目录
        $rst = Router::analysisWithURL('/sub/sub1.bbq/say', $this->dto()); // 没有参数
        $bbqSayExpect = [
            'pattern' => '/:controller/:action',
            'paths' => ['controller' => 1, 'action' => 2,],
            'pathsname' => ['controller' => 'bbq', 'action' => 'say'],
            'namespace' => "App\Http\A0\sub\Controllers\sub1",
            'viewpath' => PATH_APP . "Http" . DIRECTORY_SEPARATOR . "A0" . DIRECTORY_SEPARATOR . "sub" . DIRECTORY_SEPARATOR . "views",
            'subm' => 'sub',
            'subc' => 'sub1',
            'project' => ''
        ];
        $this->assertEquals(array_merge([
            'pickview' => 'sub1/bbq/say',
            'route' => '/sub/sub1\.([a-zA-Z0-9\_\-]+)/:action'
        ], $bbqSayExpect), $rst);
        $node = $route->getNode($rst);
        $this->assertEquals('sub/sub1.bbq/say', $node);


        $rst = Router::analysisRoutePath('/sub/sub1.bbq/say/p1', $this->dto()); // 1个参数
        $this->assertEquals(array_merge($bbqSayExpect, [
            'pattern' => '/:controller/:action/:params',
            'paths' => ['controller' => 1, 'action' => 2, 'params' => 3],
        ]), $rst);
        $node = $route->getNode($rst);
        $this->assertEquals('sub/sub1.bbq/say', $node);

        $rst = Router::analysisRoutePath('/m1/sub.c1/a1/p1/p2', $this->dto()); // 两个参数
        $this->assertEquals([
            'pattern' => '/:controller/:action/:params',
            'paths' => ['controller' => 1, 'action' => 2, 'params' => 3],
            'pathsname' => ['controller' => 'c1', 'action' => 'a1'],
            'namespace' => "App\Http\A0\m1\Controllers\sub",
            'viewpath' => PATH_APP . "Http" . DIRECTORY_SEPARATOR . "A0" . DIRECTORY_SEPARATOR . "m1" . DIRECTORY_SEPARATOR . "views",
            'subm' => 'm1',
            'subc' => 'sub',
            'project' => ''
        ], $rst);
        $node = $route->getNode($rst);
        $this->assertEquals('m1/sub.c1/a1', $node);

        // 单应用，非标准应用
        $rst = Router::analysisWithURL('/admin.simpleRent', $this->dto('city'));
//        dd($rst);
        $this->assertEquals(['controller' => 'simpleRent', 'action' => 'index'], $rst['pathsname']);
        $this->assertEquals('App\Projects\city\Controllers\admin', $rst['namespace']);
        $this->assertEquals(PATH_APP_PROJECTS . 'city' . DIRECTORY_SEPARATOR . 'views', $rst['viewpath']);


        $data = Router::analysisRoutePath('/about/us', $this->dto('city'));
        $this->assertEquals([
            'namespace' => 'App\Projects\city\Controllers',
            'viewpath' => PATH_APP_PROJECTS . 'city' . DIRECTORY_SEPARATOR . 'views'
        ], \Phax\Utils\MyData::getByKeys($data, ['namespace', 'viewpath']));

        $data = Router::analysisRoutePath('/', $this->dto('city'));
        $this->assertEquals([
            'namespace' => 'App\Projects\city\Controllers',
            'viewpath' => PATH_APP_PROJECTS . 'city' . DIRECTORY_SEPARATOR . 'views'
        ], \Phax\Utils\MyData::getByKeys($data, ['namespace', 'viewpath']));

        $data = Router::analysisRoutePath('/auth', $this->dto('city'));
        $this->assertEquals([
            'namespace' => 'App\Projects\city\Controllers',
            'viewpath' => PATH_APP_PROJECTS . 'city' . DIRECTORY_SEPARATOR . 'views'
        ], \Phax\Utils\MyData::getByKeys($data, ['namespace', 'viewpath']));
        $this->assertEquals('auth', $data['pathsname']['controller']);

        $url = '/sub/sub1.me/say?key=1';
        $data = Router::analysisWithURL($url, $this->dto());
//        dd($data);
        $this->assertEquals('/sub/sub1\.([a-zA-Z0-9\_\-]+)/:action', $data['route']);
        $this->assertEquals('sub1/me/say', $data['pickview']);


        $url = '/api/sub/sub1.me/say';
        $data = Router::analysisWithURL($url, $this->dto());
        $this->assertEquals('/api/sub/sub1\.([a-zA-Z0-9\_\-]+)/:action', $data['route']);
//        preg_match_all('#^/m/([\w0-9\_\-]+)\.wechat/([\w0-9\_\-]+)/([\w0-9\_\-]+)$#u','/m/tao.wechat/menu/edit',$matches);

    }

    public function testFormatName()
    {
        foreach (
            [
                'refreshNodeAction',
                'refreshNodeController',
                'refreshNode',
                'refresh-node',
                'refresh_node',
                'refresh node',
                'RefreshNode'
            ] as $v
        ) {
            $rst = Router::formatNodeName($v);
            $this->assertEquals(
                'refreshNode',
                $rst,
                $v . ' format failed'
            );
        }

        foreach (
            [
                'refreshNode',
                'refresh-node',
                'refresh_node',
                'RefreshNode',
            ] as $item
        ) {
            $this->assertEquals('refreshNode', Router::formatName($item));
        }
    }

    public function testCliRoute()
    {
        $rst = CliRouter::handle('/');
        $expect = [
            'task' => 'main',
            'action' => 'index',
            'namespace' => 'App\Console'
        ];
        $this->assertEquals($expect, $rst);


        $rst1 = CliRouter::handle('');
        $this->assertEquals($rst, $rst1);

        $rst = CliRouter::handle('main/test');
        $this->assertEquals(['task' => 'main', 'action' => 'test', 'namespace' => 'App\Console'], $rst);

        $rst = CliRouter::handle('p/city/main/test');
        $expect = [
            'task' => 'main',
            'action' => 'test',
            'namespace' => 'App\Projects\city\Console'
        ];
        $this->assertEquals($expect, $rst);

        $rst = CliRouter::handle('p/city');
        $expect = [
            'task' => 'main',
            'action' => 'index',
            'namespace' => 'App\Projects\city\Console'
        ];
        $this->assertEquals($expect, $rst);

// 多模块

        $rst = CliRouter::handle('m/tao1');
        $expect = [
            'task' => 'main',
            'action' => 'index',
            'namespace' => 'App\Modules\tao1\Console',
            'module' => 'tao1',
            'modules' => [
//                'tao' => [ // 需要模块存在才会这里
//                    'path' => PATH_APP_MODULES.'tao1/Module.php',
//                    'className' => 'App\Modules\tao\Module',
//                ],
                'tao1' => [
                    'path' => PATH_TAO996 . 'Phax'.DIRECTORY_SEPARATOR.'Mvc'.DIRECTORY_SEPARATOR.'Module.php',
                    'className' => 'Phax\Mvc\Module'
                ]
            ]
        ];
        $this->assertEquals($expect, $rst);


        $rst = CliRouter::handle('m/tao1/migrate');
        $expect['task'] = 'migrate';
        $this->assertEquals($expect, $rst);
    }

    /**
     * 验证 Router::analysisWithURL 和 RouteMatchContext::with 结果一致性
     * 覆盖所有路由模式：多模块、单应用、子模块、子目录、项目、API、语言前缀
     */
    public function testRouteMatchConsistency(): void
    {
        $urls = [
            // 多模块
            '/m/',
            '/cn/m/',
            '/m/m1',
            '/m/M1',
            '/en/m/m1',
            '/m/m1/c',
            '/en/m/m1/c',
            '/m/m1/c1/a1',
            '/en/m/m1/c1/a1',
            '/m/m2/c2/a2/p',
            '/en/m/m2/c2/a2/p1/p2/p3',
            // 多模块 + 子模块
            '/m/m1.m11/c1',
            '/m/m1.m11/sub1.c2',
            '/m/m1/sub1.c1',
            // 多模块 + 子目录
            '/m/m1/sub.c1/a1',
            '/m/m1/sub.c1/a1/p1',
            // 单应用
            '/',
            '/c1',
            '/c2/a2',
            '/c2/a2/p',
            '/c2/a2/p1/p2',
            '/c2/a2/p1/p2/p3',
            // 单应用 + 子目录
            '/sub.c1',
            '/sub.c2/a2',
            '/sub.c2/a2/p1',
            '/sub.c2/a2/p1/p2',
            // 页路由
            '/sub/sub1.bbq/say',
        ];

        foreach ($urls as $url) {
            $rstOld = Router::analysisWithURL($url, $this->dto());
            $rstNew = \Phax\Foundation\Context\RouteMatchContext::with($url);

            $this->compareRouteResults($url, $rstOld, $rstNew);
        }

        // 含项目的 URL 单独测试
        $projectUrl = '/api/p/family/vip/notify/wx964c9beb6dc7131b';
        $rstOld2 = Router::analysisWithURL($projectUrl, $this->dto('family'));
        $rstNew2 = \Phax\Foundation\Context\RouteMatchContext::with($projectUrl);
        // 项目路由中 namespace/viewpath 由 RouteContext 提供，RouteMatchContext 使用默认
        // 只对比基本路由结构
        $this->assertEquals($rstOld2['route'], $rstNew2->route, "route mismatch for $projectUrl");
        $this->assertEquals($rstOld2['pattern'], $rstNew2->pattern, "pattern mismatch for $projectUrl");
        $this->assertEquals($rstOld2['paths'], $rstNew2->paths, "paths mismatch for $projectUrl");
        // pathsname: only compare when RouteMatchContext has project context

        // 含项目的单应用 URL
        $url3 = '/admin.simpleRent';
        $rstOld3 = Router::analysisWithURL($url3, $this->dto('city'));
        $rstNew3 = \Phax\Foundation\Context\RouteMatchContext::with($url3);
        $this->assertEquals($rstOld3['route'], $rstNew3->route, "route mismatch for $url3");
        $this->assertEquals($rstOld3['pattern'], $rstNew3->pattern, "pattern mismatch for $url3");
    }

    /**
     * 比较 Router::analysisWithURL（数组）和 RouteMatchContext::with（对象）的关键字段
     */
    private function compareRouteResults(string $url, array $old, \Phax\Foundation\Context\RouteMatchContext $new): void
    {
        // 基本结构
        $this->assertEquals($old['pattern'] ?? '', $new->pattern, "pattern mismatch for $url");
        $this->assertEquals($old['paths'] ?? [], $new->paths, "paths mismatch for $url");
        $this->assertEquals($old['pathsname'] ?? [], $new->pathsname, "pathsname mismatch for $url");
        $this->assertEquals($old['route'] ?? '', $new->route, "route mismatch for $url");

        // namespace / viewpath（多模块模式下应该一致）
        $this->assertEquals($old['namespace'] ?? '', $new->namespace, "namespace mismatch for $url");
        $this->assertEquals($old['viewpath'] ?? '', $new->viewpath, "viewpath mismatch for $url");

        // 子模块 / 子目录
        $this->assertEquals($old['subm'] ?? '', $new->subm, "subm mismatch for $url");
        $this->assertEquals($old['subc'] ?? '', $new->subc, "subc mismatch for $url");

        // 模块信息（多模块模式下）
        if (!empty($old['module'])) {
            $this->assertEquals($old['module'], $new->modulePath, "modulePath mismatch for $url");
        }
        if (!empty($old['name'])) {
            $this->assertEquals($old['name'], $new->name, "name mismatch for $url");
        }

        // register（路径依赖文件位置，不比较，已在上方确认核心字段一致）

        // pickview
        if (!empty($old['pickview'])) {
            $this->assertEquals($old['pickview'], $new->getPickView(), "pickview mismatch for $url");
        }
    }
}