<?php

namespace App\Modules\tao\tests\PHPUnit\Helper;

use App\Modules\tao\Helper\Auth\AuthDbData;
use App\Modules\tao\Helper\Auth\AuthRedisData;
use App\Modules\tao\Helper\Auth\LoginAppAuthAdapter;
use App\Modules\tao\Helper\Auth\LoginAuthAdapter;
use App\Modules\tao\Helper\Auth\LoginDemoTokenAuthAdapter;
use App\Modules\tao\Helper\Auth\LoginSessionAuthAdapter;
use App\Modules\tao\Helper\LoginAuthHelper;
use App\Modules\tao\Models\SystemUser;
use App\Modules\tao\TaoAppService;
use Phax\Foundation\Application;
use Phax\Foundation\AppService;
use Tests\Helper\services\Request;
use PHPUnit\Framework\TestCase;

class LoginAuthHelperTest extends TestCase
{
    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new Request();
        Application::di()->set('request', $this->request);
        $this->request->data['getQuery'] = [];
        $this->request->data['getHeaders'] = [];
        $this->request->data['getContentType'] = '';
    }

    public function testJsonContentTypeAloneKeepsWebSession(): void
    {
        $this->request->data['getContentType'] = 'application/json; charset=utf-8';

        $this->assertTrue(AppService::isJsonBodyRequest());

        $helper = new LoginAuthHelper();
        $helper->setAuthAdapter();

        $this->assertInstanceOf(LoginSessionAuthAdapter::class, $helper->getAdapter());
    }

    public function testExplicitAppMarkerSelectsAppAdapter(): void
    {
        foreach ([['kind' => 'app'], ['data' => 'jsonbody']] as $query) {
            $this->request->data['getQuery'] = $query;
            $this->request->data['getContentType'] = 'application/json';

            $helper = new LoginAuthHelper();
            $helper->setAuthAdapter();

            $this->assertInstanceOf(LoginAppAuthAdapter::class, $helper->getAdapter());
        }
    }

    public function testMalformedAuthorizationReturnsAuthenticationError(): void
    {
        $this->request->data['getQuery'] = ['kind' => 'app'];
        $this->request->data['getHeaders'] = ['Authorization' => '{'];

        $helper = new LoginAuthHelper();
        $this->expectException(\Phax\Support\Exception\BusinessException::class);
        $this->expectExceptionCode(401);
        $helper->setAuthAdapter();
    }

    public function testTestTokenTakesPriorityOverJsonAppRequest(): void
    {
        $this->request->data['getQuery'] = ['test' => 'on'];
        $this->request->data['getHeaders'] = ['test-token' => 'tao'];
        $this->request->data['getContentType'] = 'application/json';

        $helper = new LoginAuthHelper();
        $helper->setAuthAdapter();

        $this->assertInstanceOf(LoginDemoTokenAuthAdapter::class, $helper->getAdapter());
    }

    public function testPlainWebRequestUsesSessionAdapter(): void
    {
        $helper = new LoginAuthHelper();
        $helper->setAuthAdapter();

        $this->assertInstanceOf(LoginSessionAuthAdapter::class, $helper->getAdapter());
    }

    public function testAppTokenKeepsThreePartFormatAndIsUnique(): void
    {
        $dbData = new AuthDbData();
        $redisData = new AuthRedisData();

        $dbToken = $dbData->generateToken(7, 'app');
        $redisToken = $redisData->generateToken(7, 'app');

        $this->assertCount(3, explode('.', $dbToken));
        $this->assertCount(3, explode('.', $redisToken));
        $this->assertStringContainsString('_', explode('.', $dbToken)[2]);
        $this->assertStringContainsString('_', explode('.', $redisToken)[2]);
        $this->assertNotSame($dbToken, $dbData->generateToken(7, 'app'));
    }

    public function testSwitchingAdapterClearsPreviousUser(): void
    {
        $first = new LoginAuthHelperTestAdapter($this->user(11));
        $second = new LoginAuthHelperTestAdapter($this->user(22));
        $helper = new LoginAuthHelper();

        $helper->setAuthAdapter($first);
        $this->assertTrue($helper->isLogin());
        $this->assertSame(11, TaoAppService::loginUserHelper()->userId());

        $helper->setAuthAdapter($second);
        $this->assertSame(0, TaoAppService::loginUserHelper()->userId());
        $helper->login();

        $this->assertTrue($helper->isLogin());
        $this->assertSame(22, TaoAppService::loginUserHelper()->userId());
    }

    public function testDisabledUserCannotBeLoaded(): void
    {
        $user = $this->user(44);
        $user->status = 100;
        $helper = new LoginAuthHelper();
        $helper->setAuthAdapter(new LoginAuthHelperTestAdapter($user));

        $this->expectException(\Phax\Support\Exception\BusinessException::class);
        $helper->login();
    }

    public function testFailedAdapterReplacementClearsPreviousUser(): void
    {
        $helper = new LoginAuthHelper();
        $helper->setAuthAdapter(new LoginAuthHelperTestAdapter($this->user(12)));
        $this->assertTrue($helper->isLogin());
        $this->assertSame(12, TaoAppService::loginUserHelper()->userId());

        $failing = new LoginAuthHelperTestAdapter($this->user(13));
        $failing->failData = true;
        try {
            $helper->setAuthAdapter($failing);
            $this->fail('Expected adapter data failure');
        } catch (\Phax\Support\Exception\BusinessException $e) {
            $this->assertSame(401, $e->getCode());
        }

        $this->assertSame(0, TaoAppService::loginUserHelper()->userId());
    }

    public function testLogoutClearsInMemoryUserEvenWhenAdapterIsDestroyed(): void
    {
        $adapter = new LoginAuthHelperTestAdapter($this->user(33), false);
        $helper = new LoginAuthHelper();
        $helper->setAuthAdapter($adapter);
        $helper->login();

        $helper->logout();

        $this->assertTrue($adapter->destroyed);
        $this->assertFalse($helper->isLogin());
        $this->assertSame(0, TaoAppService::loginUserHelper()->userId());
    }

    private function user(int $id): SystemUser
    {
        $user = new SystemUser();
        $user->id = $id;
        $user->status = 1;
        return $user;
    }
}

class LoginAuthHelperTestAdapter extends LoginAuthAdapter
{
    public bool $destroyed = false;
    public bool $failData = false;
    private bool $loggedOut = false;

    public function __construct(
        private SystemUser $user,
        private bool $clearUserOnDestroy = true,
    ) {
    }

    public static function check(): bool
    {
        return false;
    }

    public function data(): void
    {
        if ($this->failData) {
            throw new \Phax\Support\Exception\BusinessException('bad auth', [], 401);
        }
    }

    public function getUser(): SystemUser|null
    {
        return $this->loggedOut ? null : $this->user;
    }

    public function saveUser(SystemUser $user, array $info = []): mixed
    {
        $this->user = $user;
        return $user->id;
    }

    public function destroy(): void
    {
        $this->destroyed = true;
        if ($this->clearUserOnDestroy) {
            $this->loggedOut = true;
        }
    }
}
