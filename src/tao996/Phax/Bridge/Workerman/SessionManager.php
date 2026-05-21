<?php

namespace Phax\Bridge\Workerman;


use Phalcon\Session\ManagerInterface;
use Phalcon\Storage\AdapterFactory;
use Workerman\Protocols\Http\Session;

class sessionRedisAdapter extends \Phalcon\Session\Adapter\Redis
{
    public function __construct()
    {
        // nothing to do
    }
}

class SessionManager implements ManagerInterface
{
    private \Workerman\Protocols\Http\Session $session;

    public function __construct(private \Workerman\Protocols\Http\Request $request)
    {
        Session::$name = 'PHPSESSID';
        $this->session = $request->session();
    }

    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    public function __isset(string $key): bool
    {
        return $this->has($key);
    }

    public function __set(string $key, $value): void
    {
        $this->set($key, $value);
    }

    public function __unset(string $key): void
    {
        $this->remove($key);
    }

    public function exists(): bool
    {
        return session_status() === self::SESSION_ACTIVE;
    }

    public function destroy(): void
    {
        $this->session->flush();
    }

    public function get(string $key, $defaultValue = null, bool $remove = false): mixed
    {
        $value = $this->session->get($key, $defaultValue);
        if ($remove) {
            $this->session->delete($key);
        }
        return $value;
    }

    public function getId(): string
    {
        return $this->session->getId();
    }

    public function getAdapter(): \SessionHandlerInterface
    {
        return new sessionRedisAdapter();
    }

    public function getName(): string
    {
        return $this->session::$name;
    }

    public function getOptions(): array
    {
        throw new \Exception('not support getOptions in Workerman SessionManager');
    }

    public function has(string $key): bool
    {
        return $this->session->has($key);
    }

    public function remove(string $key): void
    {
        $this->session->delete($key);
        $this->session->save();
    }

    public function set(string $key, $value): void
    {
        $this->session->set($key, $value);
        $this->session->save();
    }

    public function setAdapter(\SessionHandlerInterface $adapter): ManagerInterface
    {
        throw new \Exception('not support setAdapter in Workerman SessionManager');
    }

    public function setId(string $sessionId): ManagerInterface
    {
        if (true === $this->exists()) {
            throw new \Exception(
                'The session has already been started. ' .
                'To change the id, use regenerateId()'
            );
        }
        print_r(['~~~~~~~~~~~~~~~~~~~~~~~~~~~~','newSessionId' => $sessionId]);
        $this->request->session = new Session($sessionId);
        $this->session = $this->request->session();
        return $this;
    }

    public function setName(string $name): ManagerInterface
    {
        Session::$name = $name;
        return $this;
    }

    public function setOptions(array $options): void
    {
        throw new \Exception('not support setOptions in Workerman SessionManager');
    }

    public function status(): int
    {
        return session_status();
    }

    public function start(): bool
    {
        return true;
    }

    public function regenerateId(bool $deleteOldSession = true): ManagerInterface
    {
        throw new \Exception('not support regenerateId in Workerman SessionManager');
    }
}