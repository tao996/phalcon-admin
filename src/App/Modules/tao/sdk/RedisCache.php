<?php

namespace App\Modules\tao\sdk;

use Phalcon\Cache\Exception\InvalidArgumentException;
use Phax\Foundation\AppService;
use Psr\SimpleCache\CacheInterface;

class RedisCache implements CacheInterface
{

    /**
     * @param string $key
     * @param mixed|null $default
     * @throws InvalidArgumentException
     */
    public function get(string $key, $default = null): mixed
    {
        return AppService::cache()->get($key, $default);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param \DateInterval|int|null $ttl
     * @throws InvalidArgumentException
     */
    public function set(string $key, mixed $value, \DateInterval|int $ttl = null): bool
    {
        return AppService::cache()->set($key, $value, $ttl);
    }

    /**
     * @param string $key
     * @throws InvalidArgumentException
     */
    public function delete(string $key): bool
    {
        return AppService::cache()->delete($key);
    }

    public function clear(): bool
    {
        return AppService::cache()->clear();
    }

    /**
     * @param iterable $keys
     * @param mixed|null $default
     * @return iterable
     */
    public function getMultiple(iterable $keys, $default = null): iterable
    {
        return AppService::cache()->getMultiple($keys, $default);
    }

    /**
     * @param iterable $values
     * @param \DateInterval|int|null $ttl
     * @return bool
     */
    public function setMultiple(iterable $values, \DateInterval|int $ttl = null): bool
    {
        return AppService::cache()->setMultiple($values, $ttl);
    }

    /**
     * @param iterable $keys
     * @return bool
     */
    public function deleteMultiple(iterable $keys): bool
    {
        return AppService::cache()->deleteMultiple($keys);
    }

    /**
     * @param string $key
     * @throws InvalidArgumentException
     */
    public function has(string $key): bool
    {
        return AppService::cache()->has($key);
    }
}