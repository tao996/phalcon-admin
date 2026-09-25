<?php

namespace Tests\Unit\app\Modules\tao\Config;

use App\Modules\tao\Config\Config as TaoConfig;
use App\Modules\tao\Helper\Auth\LoginAppAuthAdapter;
use App\Modules\tao\Models\SystemUser;
use Phax\Foundation\AppService;
use PHPUnit\Framework\TestCase;

class AuthConfigTest extends TestCase
{
    public function testAuthSettingsAreBuiltWithoutRecursiveConfigLookup(): void
    {
        $config = TaoConfig::authConfig();

        $this->assertSame($config['auth_token_ttl'], TaoConfig::appAuthTokenTtl());
        $this->assertSame($config['auth_timestamp_window'], TaoConfig::authTimestampWindow());
        $this->assertSame($config['auth_replay_ttl'], TaoConfig::authReplayTtl());
        $this->assertGreaterThanOrEqual(
            $config['auth_timestamp_window'] * 2,
            $config['auth_replay_ttl']
        );
    }

    public function testAppAdapterCanCreateCredential(): void
    {
        try {
            AppService::redis()->ping();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Redis is required for App adapter credential test');
        }

        $user = new SystemUser();
        $user->id = 987654;
        $user->status = 1;

        $adapter = new LoginAppAuthAdapter();
        $credential = (string) $adapter->saveUser($user);
        $parts = explode('-', $credential);

        try {
            $this->assertCount(2, $parts);
            $this->assertStringContainsString('.app.', $parts[0]);
        } finally {
            AppService::redis()->del('login:' . $parts[0]);
        }
    }
}
