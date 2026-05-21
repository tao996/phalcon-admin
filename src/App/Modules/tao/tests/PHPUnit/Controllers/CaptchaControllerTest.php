<?php

namespace App\Modules\tao\tests\PHPUnit\Controllers;

use App\Modules\tao\tests\Helper\MyTestTaoHttpHelper;
use PHPUnit\Framework\TestCase;

class CaptchaControllerTest extends TestCase
{
    public function testIndex()
    {
        MyTestTaoHttpHelper::with($this)
            ->get('m/tao/captcha')
            ->send()->notContainsFailed();

    }
}