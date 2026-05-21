<?php

namespace Phax\Bridge;

use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Encryption\Crypt;
use Phalcon\Encryption\Crypt\CryptInterface;
use Phalcon\Http\Cookie\CookieInterface;
use Phalcon\Session\ManagerInterface;
use Phax\Bridge\Traits\HttpDiTrait;

abstract class AbstractCookie extends \Phalcon\Http\Cookie implements CookieInterface, InjectionAwareInterface
{
    private const string COOKIE_PREFIX = '_PHCOOKIE_';

    use HttpDiTrait;

    /**
     * 返回 cookie 的值，用来替换掉 $_COOKIE
     * @return array
     */
    abstract protected function values(): array;

    /**
     * 用于替换默认的标准函数 'setcookie'
     * @param string $name
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     * @param string $samesite
     * @param string $priority
     * @return void
     */
    abstract protected function setCookie(
        string $name,
        string $value = '',
        int $expire = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httponly = false,
        string $samesite = '',
        string $priority = ''
    ): void;

    public function getValue($filters = null, $defaultValue = null): mixed
    {
//        var_dump(__METHOD__,$this->restored,$this->read);
        if ($this->restored) {
            $this->restore();
        }
        if ($this->read !== false) {
            return $this->value;
        }

        $values = $this->values();
        if (!isset($values[$this->name]) || !($value = $values[$this->name])) {
            return $defaultValue;
        }
        if ($this->useEncryption) {
            /**
             * @var Crypt $crypt
             */
            $crypt = $this->getDI()->get('crypt');
            $defaultValue = $this->signKey
                ? $crypt->decryptBase64($value, $this->signKey)
                : $crypt->decryptBase64($value);
        } else {
            $defaultValue = $value;
        }

        $this->value = $defaultValue;

        if ($filters) {
            if (!$this->filter) {
                $this->filter = $this->getDI()->getShared('filter');
            }
            return $this->filter->sanitize($this->value, $filters);
        }
        return $this->value;
    }


    public function send(): CookieInterface
    {
//        echo '~~~ prepare to send cookie', PHP_EOL;
//        echo '~~~ ', __CLASS__, PHP_EOL;
        $definition = [];
        $definition['expire'] = $this->expire ?? 'null';
        $definition['path'] = $this->path ?? '/';
        $definition['domain'] = $this->domain ?? '';
        $definition['secure'] = $this->secure ?? false;
        $definition['httpOnly'] = $this->httpOnly ?? false;
        $definition['samesite'] = $this->options['samesite'] ?? '';
        $definition['priority'] = $this->options['priority'] ?? '';

        /**
         * Remove all the empty elements
         */
        $definitionTrue = array_filter($definition);

        if (true !== empty($definitionTrue) &&
            null !== $this->container &&
            true === $this->container->has('session')) {
            /**
             * @var $session ManagerInterface
             */
            $session = $this->getDI()->getShared('session');
            if ($session->exists()) {
                $session->set(self::COOKIE_PREFIX . $this->name, $definitionTrue);
            }
        }
        $encryptValue = $this->value;
        if (true === $this->useEncryption && true !== empty($this->value)) {
            if (null === $this->container) {
                throw new \Exception(
                    "A dependency injection container is required to "
                    . "access the 'filter' service"
                );
            }

            /** @var CryptInterface $crypt */
            $crypt = $this->container->getShared('crypt');

            if (true !== is_object($crypt)) {
                throw new \Exception(
                    'A dependency which implements CryptInterface '
                    . 'is required to use encryption'
                );
            }

            /**
             * Encrypt the value also coding it with base64.
             * Sign the cookie's value if the sign key was set
             */
            if (is_string($this->signKey)) {
                $encryptValue = $crypt->encryptBase64(
                    (string)$this->value,
                    $this->signKey
                );
            } else {
                $encryptValue = $crypt->encryptBase64((string)$this->value);
            }
        }

        $this->setCookie(
            $this->name,
            $encryptValue,
            $definition['expire'],
            $definition['path'],
            $definition['domain'],
            $definition['secure'],
            $definition['httpOnly'],
            $definition['samesite'],
            $definition['priority']
        );
        return $this;
    }

    public function delete()
    {
        if (null !== $this->container &&
            true === $this->container->has('session')) {
            /**
             * @var $session ManagerInterface
             */
            $session = $this->getDI()->getShared('session');
            if ($session->exists()) {
                $session->remove(self::COOKIE_PREFIX . $this->name);
            }
        }
        $this->value = null;
        $this->setCookie(
            $this->name,
            null,
            time() - 691200,
            $this->path,
            $this->domain,
            $this->secure,
            $this->httpOnly
        );
    }
}