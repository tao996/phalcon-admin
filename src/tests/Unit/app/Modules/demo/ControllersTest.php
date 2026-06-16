<?php

namespace Tests\Unit\app\Modules\demo;

use PHPUnit\Framework\TestCase;
use Tests\Helper\MyTestHttpHelper;

class ControllersTest extends TestCase
{
    public function testIndex()
    {
        if (TEST_SKIP_HTTP) {
            $this->markTestSkipped();
        }
        $http = MyTestHttpHelper::with($this);

        $http->get('/m/demo')->send()
            ->notContainsFailed()
            ->contains(['views/index/index.phtml']);

        $http->get('/m/demo/index/hello')->send()
            ->notContainsFailed()
            ->contains(['name: <span>phalcon</span>']);

        $http->get('/m/demo/index/hello/phax')->send()
            ->notContainsFailed()
            ->contains(['name:','phax']);
    }

    public function testTodo()
    {
        if (TEST_SKIP_HTTP) {
            $this->markTestSkipped();
        }
        $http = MyTestHttpHelper::with($this);
        $http->get('/m/demo/todo/list')->send()
            ->notContainsFailed()
            ->contains(['todo list: <span>todo list</span>']);
    }

    public function testDbTest()
    {
        if (TEST_SKIP_HTTP) {
            $this->markTestSkipped();
        }
        $http = MyTestHttpHelper::with($this);
        $http->get('/m/demo/db.test/hello')->send()
            ->notContainsFailed()
            ->contains(['WELCOME: phax admin']);

        $http->get('/m/demo/db.test/trans')->send()
            ->notContainsFailed()
            ->contains(['cat 1 age+10'])
            ->orContains(['异常，取消事务']);
    }

    public function testA0DbTest()
    {
        if (TEST_SKIP_HTTP) {
            $this->markTestSkipped();
        }
        $http = MyTestHttpHelper::with($this);
        $http->get('/m/demo.db/test')->send()
            ->notContainsFailed()
            ->contains(['user.articles', 'user.profile', 'user.roles']);

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
        if (TEST_SKIP_HTTP) {
            $this->markTestSkipped();
        }
        $http = MyTestHttpHelper::with($this);
        $expectContent = DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR . 'demo' . DIRECTORY_SEPARATOR . 'A0' . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'views'.DIRECTORY_SEPARATOR.'user'.DIRECTORY_SEPARATOR.'info'.DIRECTORY_SEPARATOR.'name.phtml';
        $http->get('/m/demo.db/user.info/name')->send()
            ->contains([$expectContent]);

        $data = $http->get('/api/m/demo.db/user.info/name')->send()
            ->jsonResponse();
        $this->assertEquals('pha....', $data['name']);
    }
}