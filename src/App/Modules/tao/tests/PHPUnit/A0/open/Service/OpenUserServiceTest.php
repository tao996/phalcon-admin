<?php

namespace App\Modules\tao\tests\PHPUnit\A0\open\Service;

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

        // getConfig() 需返回 EasyWeChat\Kernel\Contracts\Config 实例
        $configMock = \Mockery::mock('EasyWeChat\Kernel\Contracts\Config');
        $configMock->shouldReceive('get')->with('app_id')->andReturn(self::userinfo['app_id']);
        $application->allows('getConfig')->andReturns($configMock);

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

        $us = new OpenUserService();
        $data = $us->officialUser($application, self::userinfo['openid']);
        $this->assertTrue($data['user_id'] > 0);
        $this->assertTrue(
            OpenUserUnionid::queryBuilder()
                ->where(['user_id' => $data['user_id'], 'unionid' => self::userinfo['unionid']])
                ->exits()
        );
    }
}
