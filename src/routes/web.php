<?php

/**
 * https://docs.phalcon.io/latest/routing/
 */
\Phax\Foundation\Context\RouteMatchContext::$mapRoute = [
    '/login' => '/m/tao/auth/index'
];
if (file_exists(__DIR__ . '/web.more.php')) {
    include_once __DIR__ . '/web.more.php';
}
