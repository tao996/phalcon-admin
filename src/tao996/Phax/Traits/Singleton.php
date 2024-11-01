<?php

namespace Phax\Traits;

trait Singleton
{
    private static $instance;

    /**
     * 单例 getInstance(1, 2, 3) 在 __construct($a, $b, $c) 来接收参数
     * @param ...$args
     * @return static
     */
    public static function getInstance(...$args): static
    {
        if (!isset(self::$instance)) {
            self::$instance = new static(...$args);
        }
        return self::$instance;
    }

}