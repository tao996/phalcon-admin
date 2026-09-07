<?php

namespace App\Modules\tao\Helper\Auth;

use App\Modules\tao\Models\SystemUser;
use Phax\Foundation\AppService;
use Phax\Support\Exception\BusinessException;
use Phax\Utils\MyAssert;
use Phax\Utils\MyData;

/**
 * 基于数据库的 App 登录适配器
 *
 * 与 LoginAppAuthAdapter 接口完全兼容，区别：
 * - 登录凭证存储在数据库（tao_system_user_login），无 TTL 过期
 * - 每用户最多保留 MAX_LOGIN_RECORDS 条登录记录
 * - 仅用于 kind = 'app' 场景
 * - 需要配合 appid 启用（后期通过 appid 手动启用）
 */
class LoginAppDbAuthAdapter extends LoginAuthAdapter
{
    /** 每用户最多保留的登录记录数 */
    public int $maxLoginRecords = 4;
    private AuthDbData $authDbData;

    public function __construct()
    {
        parent::__construct();
        $this->authDbData = new AuthDbData();
    }

    private array $data = [];

    public static function check(): bool
    {
        $request = AppService::request();
        return $request->hasHeader('Authorization') && $request->getQuery('kind') === 'app';

    }

    /**
     * @throws \Exception
     */
    public function data(): void
    {
        $authData = AppService::request()->getHeader('Authorization');
        if (!empty($authData)) {
            $this->data = json_decode($authData, true);
            try {
                MyAssert::mustHasSet($this->data, ['token', 't', 'sign']);
            } catch (\Exception $e) {
                throw new BusinessException('登录凭证过期或不存在.', [
                    'data' => $this->data,
                ], 401);
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function getUser(): SystemUser|null
    {
        if (!empty($this->data['token'])) {
            $userId = $this->authDbData->getUserId($this->data['token'], 'app');
            if ('logout' != AppService::context()->getActionName()) {
                $secret = $this->authDbData->getTokenValue($this->data['token'], $this->maxLoginRecords);
                if (!$secret) {
                    throw new BusinessException('登录凭证过期或不存在', [
                        'data' => $this->data,
                    ], 403);
                }

                $timestamp = intval($this->data['t']);
                $sign = md5($secret . $timestamp);
                if ($sign !== MyData::getString($this->data, 'sign')) {
                    throw new BusinessException('签名验证失败', [
                        'data' => $this->data,
                        'timestamp' => $timestamp, 'expect' => $sign
                    ]);
                }
            }
            // 数据库方案无需续期，直接查用户
            if ($user = SystemUser::findFirst($userId)) {
                return $user;
            }
        }
        return null;
    }

    private function getCacheToken(int $userId): string
    {
        return $this->authDbData
            ->generateToken($userId, 'app');
    }

    public function saveUser(SystemUser $user, array $info = []): mixed
    {
        $userId = $user->id;
        $token = $this->getCacheToken($userId);
        $sec = md5(join(',', [rand(1, 100), time() + rand(100, 9999)]));

        $this->authDbData->setToken($token, $sec, [
            'kind' => 'app',
            'ip' => AppService::request()->getClientAddress() ?? '',
            'useragent' => AppService::request()->getUserAgent() ?? '',
        ]);

        return join('-', [$token, $sec]);
    }

    public function destroy(): void
    {
        if (isset($this->data['token'])) {
            $this->authDbData->delToken($this->data['token']);
        }
    }
}
