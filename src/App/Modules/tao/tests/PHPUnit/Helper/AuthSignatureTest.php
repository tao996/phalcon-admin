<?php

namespace App\Modules\tao\tests\PHPUnit\Helper;

use App\Modules\tao\Helper\Auth\AuthSignature;
use Phax\Foundation\AppService;
use Phax\Support\Exception\BusinessException;
use PHPUnit\Framework\TestCase;

class AuthSignatureTest extends TestCase
{
    private string $token;
    private string $secret;
    private string $nonce;
    private string $replayKey;

    protected function setUp(): void
    {
        parent::setUp();
        try {
            AppService::redis()->ping();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Redis is required for v2 replay tests');
        }

        $this->token = '1.app.' . time() . '_test';
        $this->secret = 'test-secret';
        $this->nonce = bin2hex(random_bytes(16));
        $this->replayKey = 'auth:nonce:' . hash(
            'sha256',
            $this->token . "\0" . $this->nonce
        );
    }

    protected function tearDown(): void
    {
        if (isset($this->replayKey)) {
            AppService::redis()->del($this->replayKey);
        }
        parent::tearDown();
    }

    public function testV2SignatureAcceptsOnceAndRejectsReplay(): void
    {
        $data = $this->v2Data();
        AuthSignature::verify($data, $this->secret);

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(401);
        AuthSignature::verify($data, $this->secret);
    }

    public function testLegacySignatureRemainsCompatible(): void
    {
        $timestamp = time();
        AuthSignature::verify([
            'token' => $this->token,
            't' => $timestamp,
            'sign' => md5($this->secret . $timestamp),
        ], $this->secret);
        $this->addToAssertionCount(1);
    }

    public function testV2RejectsStaleTimestampBeforeClaimingNonce(): void
    {
        $data = $this->v2Data();
        $data['t'] = time() - 3600;
        $data['sign'] = hash_hmac(
            'sha256',
            $this->token . "\n" . $data['t'] . "\n" . $this->nonce,
            $this->secret
        );

        $this->expectException(BusinessException::class);
        $this->expectExceptionCode(401);
        AuthSignature::verify($data, $this->secret);
    }

    private function v2Data(): array
    {
        $timestamp = time();
        return [
            'v' => 2,
            'alg' => 'hmac-sha256',
            'token' => $this->token,
            't' => $timestamp,
            'nonce' => $this->nonce,
            'sign' => hash_hmac(
                'sha256',
                $this->token . "\n" . $timestamp . "\n" . $this->nonce,
                $this->secret
            ),
        ];
    }
}
