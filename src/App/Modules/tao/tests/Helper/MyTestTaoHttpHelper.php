<?php

namespace App\Modules\tao\tests\Helper;

use App\Modules\tao\Helper\Auth\LoginDemoTokenAuthAdapter;
use Tests\Helper\MyTestHttpHelper;

class MyTestTaoHttpHelper extends MyTestHttpHelper
{
    public static function with(\PHPUnit\Framework\TestCase $tc): MyTestTaoHttpHelper
    {
        return new static($tc);
    }

    /**
     * 用户登录
     * 配置文件 src/config/project.php `['test'=>['tokens'=>['tao'=>1]]]`
     * src/app/Modules/tao/Helper/Auth/LoginDemoTokenAuthAdapter.php
     * @return $this
     */
    public function login(string $token = 'tao'): static
    {
        $this->myCurl->addHeader(LoginDemoTokenAuthAdapter::HeaderKeyName, $token);
        return $this;
    }

    public function setJsonBody(array $data): static
    {
        $this->myCurl->setJsonBody($data);
        return $this;
    }

    /**
     * 执行 login() -> send() -> notContainsFailed() -> jsonResponseData() 后，
     * 注意：返回的是 jsonResponseData()
     * @return mixed
     */
    public function loginSendJsonResponse(): mixed
    {
        return $this->login()
            ->send()
            ->notContainsFailed()
            ->jsonResponseData();
    }
}