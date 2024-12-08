<?php

namespace Tests\Helper\services;

use Phalcon\Session\Adapter\Noop;
use Phalcon\Session\Manager;
use SessionHandlerInterface;

/**
 * @link https://docs.phalcon.io/5.0/en/session
 */
class Session extends Manager
{
    /**
     * @param SessionHandlerInterface|null $adapter
     */
    public function setAdapter(?SessionHandlerInterface $adapter): \Phalcon\Session\ManagerInterface
    {
        $this->adapter = new Noop();
        return $this;
    }

    public function getAdapter(): SessionHandlerInterface
    {
        return $this->adapter ?? new Noop();
    }

    public array $data = [];

    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function get(string $key, $defaultValue = null, bool $remove = false): mixed
    {
        $v = $this->data[$key] ?? $defaultValue;
        if ($remove && isset($this->data[$key])) {
            unset($this->data[$key]);
        }
        return $v;
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    public function destroy(): void
    {
        $this->data = [];
    }
}