<?php

namespace App\Modules\tao\Helper\Auth;

use App\Modules\tao\Config\Config;
use App\Modules\tao\Models\SystemUserLogin;
use Phax\Support\Exception\BusinessException;

/**
 * 数据库登录凭证管理。
 *
 * App 凭证默认采用 1 年滑动有效期：有效请求在达到续期阈值后更新
 * updated_at；超过有效期未活动则失效，主动 logout 会删除当前 token。
 * 每用户最大记录数由 app.auth_max_login_records 配置，0 表示不自动挤出。
 */
class AuthDbData
{
    /**
     * 为指定用户生成一个不重复的 token。
     * 格式仍为 userId.kind.opaque，第三段不再仅使用秒级时间。
     */
    public function generateToken(int $userId, string $kind): string
    {
        $nonce = bin2hex(random_bytes(8));
        return join('.', [$userId, $kind, time() . '_' . $nonce]);
    }

    /**
     * 从 token 字符串解析 userId。
     */
    public function getUserId(string $token, string $kind): int
    {
        $tokenData = explode('.', $token);
        if (count($tokenData) != 3) {
            throw new BusinessException('用户登录凭证错误', [
                'error' => 'count($tokenData) != 3',
                'kind' => $kind,
            ], 401);
        }

        if (intval($tokenData[0]) < 1 || $tokenData[1] != $kind) {
            throw new BusinessException('用户登录凭证错误', [
                'kind' => $kind,
            ], 401);
        }

        return (int) $tokenData[0];
    }

    /**
     * 保存登录记录到数据库。
     */
    public function setToken(string $token, mixed $value, array $extra = []): void
    {
        $kind = $extra['kind'] ?? 'app';
        $userId = $this->getUserId($token, $kind);

        $record = new SystemUserLogin();
        $record->user_id = $userId;
        $record->token = $token;
        $record->secret = (string) $value;
        $record->kind = $kind;
        $record->ip = $extra['ip'] ?? '';
        $record->useragent = $extra['useragent'] ?? '';

        if (!$record->save()) {
            throw new BusinessException('保存登录记录失败', [
                'errors' => $record->getErrors(true),
            ]);
        }

        $this->pruneExpiredTokens($userId, $kind);
        $this->pruneOldTokens($userId, $kind);
    }

    /**
     * 获取 token 对应的有效 secret。
     */
    public function getTokenValue(string $token, int $limit = 0): ?string
    {
        $record = $this->findActiveToken($token, $limit);
        return $record?->secret;
    }

    /**
     * 删除指定 token 的全部登录记录，避免重复 token 留下可用副本。
     */
    public function delToken(string $token): void
    {
        $userId = $this->getUserId($token, 'app');
        SystemUserLogin::queryBuilder()
            ->int('user_id', $userId)
            ->string('token', $token)
            ->string('kind', 'app')
            ->delete();
    }

    /**
     * 获取 token 的剩余有效期；数据库方案没有记录时返回 0。
     */
    public function getTtl(string $token): int
    {
        $record = $this->findActiveToken($token);
        if (!$record) {
            return 0;
        }

        return max(0, $this->lastActivityAt($record) + Config::appAuthTokenTtl() - time());
    }

    /**
     * 在签名验证成功后滑动续期。
     */
    public function setTokenExpire(string $token, int $seconds = 0): void
    {
        $this->touchToken($token);
    }

    private function pruneExpiredTokens(int $userId, string $kind): void
    {
        $cutoff = time() - Config::appAuthTokenTtl();
        $records = SystemUserLogin::queryBuilder()
            ->int('user_id', $userId)
            ->string('kind', $kind)
            ->findModels();

        foreach ($records as $record) {
            if ($this->lastActivityAt($record) <= $cutoff) {
                $record->delete();
            }
        }
    }

    /**
     * 按配置清理旧登录记录。0 表示不自动挤出旧设备。
     */
    private function pruneOldTokens(int $userId, string $kind): void
    {
        $maxRecords = Config::appAuthMaxLoginRecords();
        if ($maxRecords < 1) {
            return;
        }

        $records = SystemUserLogin::queryBuilder()
            ->int('user_id', $userId)
            ->string('kind', $kind)
            ->orderBy('updated_at desc, id desc')
            ->findModels();

        if (count($records) <= $maxRecords) {
            return;
        }

        foreach (array_slice($records, $maxRecords) as $record) {
            $record->delete();
        }
    }

    private function findActiveToken(string $token, int $limit = 0): ?SystemUserLogin
    {
        $userId = $this->getUserId($token, 'app');
        $query = SystemUserLogin::queryBuilder()
            ->int('user_id', $userId)
            ->string('token', $token)
            ->string('kind', 'app')
            ->orderBy('id desc');

        if ($limit > 0) {
            $query->limit($limit);
        }

        foreach ($query->findModels() as $record) {
            if ($this->isActive($record)) {
                return $record;
            }
        }

        return null;
    }

    private function touchToken(string $token): void
    {
        $record = $this->findActiveToken($token);
        if (!$record) {
            return;
        }

        $refreshAfter = max(1, intdiv(Config::appAuthTokenTtl(), 2));
        if (time() - $this->lastActivityAt($record) < $refreshAfter) {
            return;
        }

        $record->updated_at = time();
        if (!$record->save()) {
            throw new BusinessException('更新登录凭证有效期失败', [
                'errors' => $record->getErrors(true),
            ]);
        }
    }

    private function lastActivityAt(SystemUserLogin $record): int
    {
        return max((int) $record->updated_at, (int) $record->created_at);
    }

    private function isActive(SystemUserLogin $record): bool
    {
        $lastActivity = $this->lastActivityAt($record);
        return $lastActivity > 0
            && $lastActivity + Config::appAuthTokenTtl() > time();
    }
}
