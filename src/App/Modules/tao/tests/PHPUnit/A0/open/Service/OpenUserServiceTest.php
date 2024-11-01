<?php

namespace App\Modules\tao\tests\PHPUnit\A0\open\Service;

use App\Modules\tao\A0\open\Helper\MyOpenMvcHelper;
use App\Modules\tao\A0\open\Models\OpenUserUnionid;
use App\Modules\tao\A0\open\Service\OpenUserService;
use EasyWeChat\OfficialAccount\Application;
use PHPUnit\Framework\TestCase;

class OpenUserServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        \Mockery::close();
    }

    const array userinfo = [
        'app_id' => '123456',
        'openid' => 'op-abc123',
        'headimgurl' => 'https://test.com/a.png',
        'unionid' => 'un-def456',
        'nickname' => 'test123',
        'sex' => 0,
        'language' => '',
        'city' => '',
        'country' => '',
        'subscribe_time' => 1700189523,
    ];

    public function testOfficialUser()
    {
        $application = \Mockery::mock(Application::class);
        $application->allows('getConfig')->andReturns(
            new class {
                public function get($key)
                {
                    return OpenUserServiceTest::userinfo[$key];
                }
            }
        );
        $application->allows('getClient')->andReturn(
            new class {
                public function get($url, $params)
                {
                    return new class {
                        public function toArray()
                        {
                            return OpenUserServiceTest::userinfo;
                        }
                    };
                }
            }
        );
//        $mvc = new MyOpenMvcHelper(new MyMvcHelper(\Phax\Foundation\Application::di()));
        $mvc = \Mockery::mock(MyOpenMvcHelper::class);
        $us = new OpenUserService($mvc);
        $data = $us->officialUser($application, self::userinfo['openid']);
        $this->assertTrue($data['user_id'] > 0);
        $this->assertTrue(
            OpenUserUnionid::queryBuilder()
                ->where(['user_id' => $data['user_id'], 'unionid' => self::userinfo['unionid']])
                ->exits()
        );
    }
}
