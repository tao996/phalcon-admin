<?php

namespace App\Modules\tao\Helper\Auth;

use App\Modules\tao\Models\SystemUserLogin;
use Phax\Support\Exception\BusinessException;

/**
 * 数据库登录凭证管理
 *
 * 与 AuthRedisData 接口对齐，将 token/secret 存储在数据库中，
 * 无 TTL 过期机制（永不过期），通过 pruneOldTokens 控制每用户最大登录数。
 * 仅用于 kind = 'app' 场景。
 */
class AuthDbData
{
    /** 每用户最多保留的登录记录数 */
    private const int MAX_LOGIN_RECORDS = 4;

    /**
     * 为指定用户生成一个 token
     * 格式：userId.kind.timestamp（与 AuthRedisData 保持一致）
     */
    public function generateToken(int $userId, string $kind): string
    {
        return join('.', [$userId, $kind, time()]);
    }

    /**
     * 从 token 字符串解析 userId
     */
    public function getUserId(string $token, string $kind): int
    {
        $tokenData = explode('.', $token);
        if (count($tokenData) != 3) {
            throw new BusinessException('用户登录凭证错误', [
                'error' => 'count($tokenData) != 3',
                'token' => $token, 'kind' => $kind, 'tokenData' => $tokenData,
            ]);
        }

        if (intval($tokenData[0]) < 1 || $tokenData[1] != $kind) {
            throw new BusinessException('用户登录凭证错误', [
                'token' => $token, 'kind' => $kind, 'tokenData' => $tokenData,
            ]);
        }

        return $tokenData[0];
    }

    /**
     * 保存登录记录到数据库
     */
    public function setToken(string $token, mixed $value, array $extra = []): void
    {
        $userId = $this->getUserId($token, $extra['kind'] ?? 'app');

        $record = new SystemUserLogin();
        $record->user_id = $userId;
        $record->token = $token;
        $record->secret = (string) $value;
        $record->kind = $extra['kind'] ?? 'app';
        $record->ip = $extra['ip'] ?? '';
        $record->useragent = $extra['useragent'] ?? '';

        if (!$record->save()) {
            throw new BusinessException('保存登录记录失败', [
                'errors' => $record->getErrors(true),
            ]);
        }

        // 修剪旧记录，保留最新 N 条
//        $this->pruneOldTokens($userId, $extra['kind'] ?? 'app');
    }

    /**
     * 获取 token 对应的 secret
     */
    public function getTokenValue(string $token, int $limit =4): ?string
    {
        $userId = $this->getUserId($token, 'app');
        $record = SystemUserLogin::queryBuilder()->int('user_id',$userId)
            ->string('token',$token)
            ->orderBy(['id DESC'])->limit($limit)->findFirstModel();

        return $record?->secret;
    }

    /**
     * 删除指定 token 的登录记录
     */
    public function delToken(string $token): void
    {
        $userId = $this->getUserId($token, 'app');
        $record = SystemUserLogin::findFirst([
            'conditions' => 'token = :token: AND user_id = :userId:',
            'bind' => ['token' => $token, 'userId' => $userId],
            'columns' => 'id',
        ]);

        if ($record) {
            $record->delete();
        }
    }

    /**
     * 获取 token 的剩余有效期（数据库方案永不过期，返回 0）
     */
    public function getTtl(string $token): int
    {
        return 0;
    }

    /**
     * 设置 token 过期时间（数据库方案无需操作）
     */
    public function setTokenExpire(string $token, int $seconds): void
    {
        // 数据库方案不需要续期，no-op
    }

    /**
     * 修剪用户旧登录记录，仅保留最新的 N 条
     */
    private function pruneOldTokens(int $userId, string $kind): void
    {
        // 查出该用户所有登录记录，按创建时间降序
        $records = SystemUserLogin::queryBuilder()
            ->int('user_id', $userId)
            ->string('kind', $kind)
            ->orderBy(['id DESC'])
            ->findModels();

        $count = count($records);
        if ($count <= self::MAX_LOGIN_RECORDS) {
            return;
        }

        // 删除超出的旧记录
        $toDelete = array_slice($records, self::MAX_LOGIN_RECORDS);
        foreach ($toDelete as $record) {
            $record->delete();
        }
    }
}
