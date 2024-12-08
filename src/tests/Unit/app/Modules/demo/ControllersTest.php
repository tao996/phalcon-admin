<?php

namespace Tests\Unit\app\Modules\demo;

use PHPUnit\Framework\TestCase;
use Tests\Helper\MyTestHttpHelper;

class ControllersTest extends TestCase
{
    public function testIndex()
    {
        $http = MyTestHttpHelper::with($this);

        $http->get('/m/demo')->send()
            ->notContainsFailed()
            ->contains(['views/index/index.phtml']);

        $http->get('/m/demo/index/hello')->send()
            ->notContainsFailed()
            ->contains(['name: <span>phalcon</span>']);

        $http->get('/m/demo/index/hello/phax')->send()
            ->notContainsFailed()
            ->contains(['name: <span>phax</span>']);
    }

    public function testTodo()
    {
        $http = MyTestHttpHelper::with($this);
        $http->get('/m/demo/todo/list')->send()
            ->notContainsFailed()
            ->contains(['todo list: <span>todo list</span>']);
    }

    public function testDbTest()
    {
        $http = MyTestHttpHelper::with($this);
        $http->get('/m/demo/db.test/hello')->send()
            ->notContainsFailed()
            ->contains(['<div>WELCOME: phax admin</div>']);

        $http->get('/m/demo/db.test/trans')->send()
            ->notContainsFailed()
            ->contains(['cat 1 age+10'])
            ->orContains(['随机异常，取消事务', 'db 事务演示结果查询']);
    }

    public function testA0DbTest()
    {
        $http = MyTestHttpHelper::with($this);
        $http->get('/m/demo.db/test')->send()
            ->notContainsFailed()
            ->contains(['[language] => cn', 'user.articles', 'user.profile', 'user.roles']);

        $http->get('/m/demo.db/test/insert')->send()
            ->notContainsFailed()
            ->contains(['新添加记录', '[api]', '[id] =>']);

        $http->get('/m/demo.db/test/remove')->send()
            ->notContainsFailed()
            ->contains(['TRUE', 'Integer', 'deleted_at']);

        $data = $http->get('/m/demo.db/test/list')->send()
            ->notContainsFailed()
            ->jsonResponse();
        $this->assertTrue(count($data['all']) > 0);

        $http->get('/m/demo.db/test/form')->send()
            ->notContainsFailed()
            ->contains(['表单验证']);
    }

    public function testA0DbUserInfo()
    {
        $http = MyTestHttpHelper::with($this);
        $http->get('/m/demo.db/user.info/name')->send()
            ->contains(['/var/www/App/Modules/demo/A0/db/views/user/info/name.phtml']);

        $data = $http->get('/api/m/demo.db/user.info/name')->send()
            ->jsonResponse();
        $this->assertEquals('pha....', $data['name']);
    }
}