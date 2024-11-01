<?php

namespace Phax\Support\Facade;


abstract class Facade
{
    private static array $resolvedInstances = [];

    protected static function getFacadeName(): string
    {
        throw new \Exception('you should implement Facade name');
    }

    protected static function getFacadeObject()
    {
        throw new \Exception('you should implement Facade object');
    }

    private static function resolveInstance($name)
    {
        if (!isset(static::$resolvedInstances[$name])) {
            static::$resolvedInstances[$name] = static::getFacadeObject();
        }
        return static::$resolvedInstances[$name];
    }

    private static function getFacadeRoot()
    {
        return static::resolveInstance(static::getFacadeName());
    }

    public static function __callStatic($method, $args)
    {
        $instance = static::getFacadeRoot();
        if (!$instance) {
            throw new \Exception('a Facade is not set');
        }
        return $instance->$method(...$args);
    }
}