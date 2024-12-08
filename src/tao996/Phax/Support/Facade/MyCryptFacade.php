<?php

namespace Phax\Support\Facade;

/**
 * @link https://docs.phalcon.io/5.0/en/encryption-crypt
 * @method static string encrypt(string $text, string $key) 数据加密
 * @method static string decrypt(string $encrypted, string $key) 数据解密
 */
class MyCryptFacade extends Facade
{
    protected static function getFacadeObject()
    {
        return new \Phalcon\Encryption\Crypt();
    }

    protected static function getFacadeName(): string
    {
        return 'crypt';
    }
}