<?php

namespace Phax\Mvc;

use Phalcon\Di\DiInterface;
use Phalcon\Mvc\ModuleDefinitionInterface;

class Module implements ModuleDefinitionInterface
{
    public function registerAutoloaders(DiInterface $container = null)
    {

    }

    public function registerServices(DiInterface $container)
    {
    }
}