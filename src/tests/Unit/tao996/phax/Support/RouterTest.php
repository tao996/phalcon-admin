<?php

declare(strict_types=1);

namespace Tests\Unit\tao996\phax\Support;

use Phax\Foundation\CliRouter;
use Phax\Support\Router;


class RouterTest extends \PHPUnit\Framework\TestCase
{

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