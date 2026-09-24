<?php

namespace App\Modules\tao\tests\PHPUnit\Helper;

use App\Modules\tao\Config\Config;
use App\Modules\tao\Helper\Auth\AuthDbData;
use App\Modules\tao\Models\SystemUserLogin;
use PHPUnit\Framework\TestCase;

class AuthDbDataTest extends TestCase
{
    private AuthDbData $data;
    /** @var string[] */
    private array $tokens = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->data = new AuthDbData();
    }

    protected function tearDown(): void
    {
        foreach ($this->tokens as $token) {
            $this->data->delToken($token);
        }
        parent::tearDown();
    }

    public function testTokenRoundTripAndDelete(): void
    {
        $token = $this->newToken();
        $secret = bin2hex(random_bytes(16));

        $this->data->setToken($token, $secret, ['kind' => 'app']);

        $this->assertSame($secret, $this->data->getTokenValue($token));
        $this->assertGreaterThan(0, $this->data->getTtl($token));

        $this->data->delToken($token);
        $this->assertNull($this->data->getTokenValue($token));
    }

    public function testMalformedTokenUsesAuthenticationErrorCode(): void
    {
        $this->expectException(\Phax\Support\Exception\BusinessException::class);
        $this->expectExceptionCode(401);
        $this->data->getUserId('bad-token', 'app');
    }

    public function testExpiredTokenIsRejected(): void
    {
        $token = $this->newToken();
        $this->data->setToken($token, bin2hex(random_bytes(16)), ['kind' => 'app']);

        $record = SystemUserLogin::queryBuilder()
            ->string('token', $token)
            ->findFirstModel();
        $this->assertNotNull($record);

        SystemUserLogin::layer()->update([
            'created_at' => time() - Config::appAuthTokenTtl() - 10,
            'updated_at' => time() - Config::appAuthTokenTtl() - 10,
        ], ['id' => $record->id]);

        $this->assertNull($this->data->getTokenValue($token));
    }

    private function newToken(): string
    {
        $token = $this->data->generateToken(999999, 'app');
        $this->tokens[] = $token;
        return $token;
    }
}
