<?php

namespace Tests\Helper;

use App\Modules\tao\Models\SystemUser;
use Phax\Foundation\Application;
use Phax\Helper\MyMvc;

class MyTestMvc extends MyMvc
{
    private static MyTestMvc|null $_instance = null;

    private function __construct(\Phalcon\Di\Di $di)
    {
        parent::__construct($di);
    }

    /**
     * @return MyTestMvc 单例方法,用于访问实例的公共的静态方法
     */
    public static function getInstance(): MyTestMvc
    {
        if (self::$_instance == null) {
            self::$_instance = new self(Application::di());
        }
        return self::$_instance;
    }


    public function getLoginUser(string $token = 'tao')
    {
        $tokens = $this->config()->path('app.test.tokens')->toArray();
        if ($userId = $tokens[$token]) {
            return SystemUser::findFirst($userId);
        } else {
            throw new \Exception('token(' . $token . ') not found in app.test.tokens');
        }

    }
}