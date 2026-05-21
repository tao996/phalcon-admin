<?php

namespace App\Modules\tao\sdk;

use App\Modules\tao\Helper\MyMvcHelper;
use Phalcon\Cache\Exception\InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;

class RedisCache implements CacheInterface
{
    private \Phalcon\Cache\Cache $cache;

    public function __construct(protected MyMvcHelper $helper)
    {
        $this->cache = $this->helper->cache();
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @throws InvalidArgumentException
     */
    public function get($key, $default = null): mixed
    {
        return $this->cache->get($key, $default);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param \DateInterval|int|null $ttl
     * @throws InvalidArgumentException
     */
    public function set($key, $value, $ttl = null): bool
    {
        return $this->cache->set($key, $value, $ttl);
    }

    /**
     * @param string $key
     * @throws InvalidArgumentException
     */
    public function delete($key): bool
    {
        return $this->cache->delete($key);
    }

    public function clear(): bool
    {
        return $this->cache->clear();
    }

    /**
     * @param iterable $keys
     * @param mixed|null $default
     * @return iterable
     */
    public function getMultiple($keys, $default = null): iterable
    {
        $values = $this->cache->getMultiple($keys, $default);
        return $values;
    }

    /**
     * @param iterable $values
     * @param \DateInterval|int|null $ttl
     * @return bool
     */
    public function setMultiple($values, $ttl = null): bool
    {
        return $this->cache->setMultiple($values, $ttl);
    }

    /**
     * @param iterable $keys
     * @return bool
     */
    public function deleteMultiple($keys): bool
    {
        return $this->cache->deleteMultiple($keys);
    }

    /**
     * @param string $key
     * @throws InvalidArgumentException
     */
    public function has($key): bool
    {
        return $this->cache->has($key);
    }
}