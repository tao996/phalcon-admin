<?php

namespace Phax\Bridge;

use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Http\Cookie\CookieInterface;
use Phalcon\Http\Response\CookiesInterface;
use Phax\Bridge\Traits\HttpDiTrait;

/**
 * \Phalcon\Http\Response\Cookies use the \Phalcon\Http\Cookie
 */
class AbstractResponseCookies extends \Phalcon\Http\Response\Cookies implements CookiesInterface,
                                                                                InjectionAwareInterface
{
    use HttpDiTrait;

    public static string $cookieClassName = '';

    public function get(string $name): CookieInterface
    {
//        var_dump(
//            '~~~~~~~~~~~',__METHOD__,
//            $name,
//            isset($this->cookies[$name])
//        );
        if (isset($this->cookies[$name])) {
            return $this->cookies[$name];
        }
        /**
         * @var $cookie AbstractCookie
         */
        $cookie = new self::$cookieClassName($name);
        $cookie->setHttpDi($this->httpDi);
        if (true === $this->useEncryption) {
            $cookie->useEncryption($this->useEncryption);
            $cookie->setSignKey($this->signKey);
        }

        return $cookie;
    }

    public function set(
        string $name,
        $value = null,
        int $expire = 0,
        string $path = '/',
        bool $secure = null,
        string $domain = null,
        bool $httpOnly = null,
        array $options = []
    ): CookiesInterface {
        /**
         * @var $cookie AbstractCookie
         */
        if (true !== isset($this->cookies[$name])) {
            $cookie = new self::$cookieClassName(
                $name, $value, $expire, $path,
                $secure, $domain, $httpOnly, $options
            );
            $cookie->setHttpDi($this->httpDi);
            if (true === $this->useEncryption) {
                $cookie->useEncryption($this->useEncryption);
                $cookie->setSignKey($this->signKey);
            }

            $this->cookies[$name] = $cookie;
        } else {
            $cookie = $this->cookies[$name];
            $cookie
                ->setValue($value)
                ->setExpiration($expire)
                ->setPath($path)
                ->setSecure($secure)
                ->setDomain($domain)
                ->setHttpOnly($httpOnly)
                ->setOptions($options)
                ->setSignKey($this->signKey);
        }
        if (true !== $this->registered) {
//            echo __CLASS__, 'set a cookie', PHP_EOL;
            /**
             * @var $response AbstractResponse
             */
            $response = $this->httpDi->getShared('response');
            $response->setCookies($this);
            $this->registered = true;
        }

        return $this;
    }
}