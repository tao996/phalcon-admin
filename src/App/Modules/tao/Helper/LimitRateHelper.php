<?php

namespace App\Modules\tao\Helper;

/**
 * 检查操作频繁
 */
class LimitRateHelper
{
    private readonly string $key;


    /**
     * @param string $action 操作名称
     * @param int $userId 用户 ID
     */
    public function __construct(private \Redis $redis, public string $action, public int $userId = 0)
    {
        $this->key = "limitation:$action:$userId";
    }

    /**
     * 能否继续操作
     * @return bool
     * @throws \RedisException
     */
    public function disable(): bool
    {
        return $this->redis->get($this->key) !== false;
    }

    /**
     * 写入限制时间
     * @param int|null|array $option 如果是数字，则表示设置为到期秒数 ['EX'=>15]
     * @return void
     * @throws \RedisException
     */
    public function save(int|array|null $option = 15): void
    {
        $this->redis->set($this->key, 1, is_int($option) ? ['EX' => $option] : $option);
    }

    public function incr(int $incrValue = 1): void
    {
        $this->redis->incrBy($this->key, $incrValue);
    }

    /**
     * 当天记数+1
     * @return void
     * @throws \RedisException
     */
    public function todayIncr(): void
    {
        if ($this->redis->exists($this->key)) {
            $this->incr();
        } else {
            $secondsLeft = 86400 - time() % 86400;
            $this->save($secondsLeft);
        }
    }

    /**
     * 保存的值
     * @return int
     * @throws \RedisException
     */
    public function get(): int
    {
        return intval($this->redis->get($this->key) ?: 0);
    }
    public function getValue()
    {
        return $this->redis->get($this->key);
    }

    /**
     * 大于等于指定的值
     * @param int $maxValue
     * @return bool
     * @throws \RedisException
     */
    public function gtoe(int $maxValue)
    {
        $v = $this->redis->get($this->key);
        if (empty($v)) {
            return false;
        }
        return intval($v) >= $maxValue;
    }

    /**
     * 清除
     * @return void
     * @throws \RedisException
     */
    public function clear(): void
    {
        $this->redis->del($this->key);
    }
}