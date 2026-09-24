<?php

namespace App\Modules\tao\Helper\Auth;

use App\Modules\tao\Config\Config;
use App\Modules\tao\Models\SystemUser;
use App\Modules\tao\Services\UserService;
use Phax\Foundation\AppService;
use Phax\Support\Exception\BusinessException;
use Phax\Utils\MyAssert;
use Phax\Utils\MyData;

/**
 * 基于数据库的 App 登录适配器
 *
 * 与 LoginAppAuthAdapter 接口完全兼容，区别：
 * - 登录凭证存储在数据库（tao_system_user_login）
 * - 默认采用 1 年滑动有效期
 * - 每用户最大记录数由配置控制，0 表示不自动挤出旧设备
 * - 仅用于 kind = 'app' 场景
 */
class LoginAppDbAuthAdapter extends LoginAuthAdapter
{
    /** 每用户最多保留的登录记录数；0 表示不自动挤出。 */
    public int $maxLoginRecords = 0;
    private AuthDbData $authDbData;

    public function __construct()
    {
        parent::__construct();
        $this->maxLoginRecords = Config::appAuthMaxLoginRecords();
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
            $decoded = json_decode($authData, true);
            if (!is_array($decoded)) {
                throw new BusinessException('登录凭证格式错误', [], 401);
            }
            $this->data = $decoded;

            $required = ['token'];
            if ('logout' != AppService::context()->getActionName()) {
                $required[] = 't';
                $required[] = 'sign';
            }
            try {
                MyAssert::mustHasSet($this->data, $required);
            } catch (\Exception $e) {
                throw new BusinessException('登录凭证过期或不存在.', [], 401);
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function getUser(): SystemUser|null
    {
        if (empty($this->data['token'])) {
            return null;
        }

        $token = $this->data['token'];
        $userId = $this->authDbData->getUserId($token, 'app');
        $verifySignature = 'logout' != AppService::context()->getActionName();

        if ($verifySignature) {
            $secret = $this->authDbData->getTokenValue($token, $this->maxLoginRecords);
            if (!$secret) {
                throw new BusinessException('登录凭证过期或不存在', [], 401);
            }

            $timestamp = intval($this->data['t']);
            $sign = md5($secret . $timestamp);
            $clientSign = MyData::getString($this->data, 'sign');
            if (!hash_equals($sign, $clientSign)) {
                throw new BusinessException('签名验证失败', [
                    'timestamp' => $timestamp,
                ], 401);
            }
        }

        if ($user = SystemUser::findFirst($userId)) {
            if ($verifySignature) {
                UserService::activeStatus($user);
                // 只有账号和签名都有效后才续期。
                $this->authDbData->setTokenExpire($token);
            }
            return $user;
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
        $sec = bin2hex(random_bytes(16));

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
