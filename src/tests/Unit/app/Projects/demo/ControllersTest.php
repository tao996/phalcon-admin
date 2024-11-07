<?php

namespace Tests\Unit\app\Projects\demo;

use App\Projects\demo\Controllers\IndexController;
use Tests\Helper\MyTestControllerHelper;
use Tests\Helper\MyTestHttpHelper;

class ControllersTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @throws \Exception
     */
    public function testIndex()
    {
        // http test
        $http = MyTestHttpHelper::with($this);
//        ddd($_ENV);
        $http->get('/p/demo')->send()
            ->notContainsFailed()
            ->contains(['- 应用']);

        // action test
        /**
         * @var IndexController $cc
         */
        list($tc, $cc) = MyTestControllerHelper::with(IndexController::class);
        $rst = $cc->indexAction();
        $this->assertEquals('Phalcon', $rst['name']);
    }
}