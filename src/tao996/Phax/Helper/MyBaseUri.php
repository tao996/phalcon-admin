<?php

namespace Phax\Helper;

use Phalcon\Di\Di;
use Phalcon\Http\RequestInterface;
use Phax\Support\Config;

class MyBaseUri
{
    private string $origin = '';
    private RequestInterface $request;
    private Config $config;

    public function __construct(Di $di)
    {
        $this->request = $di->get('request');
        $this->config = $di->get('config');
    }

    public function getOrigin(): string
    {

        if (empty($this->origin)) {
            $scheme = $this->request->hasServer('HTTPS')
            && (($this->request->getServer('HTTPS') == 'on') || ($this->request->getServer('HTTPS') == 1))
                ? 'https' : 'http';
            if ($this->config->path('app.https')) {
                $scheme = 'https';
            }
            $port = '';
            $server_port = $this->request->getServer('SERVER_PORT') ?: ($_SERVER['OPEN_PORT'] || '80');
            if ($server_port != '80' && $server_port != '443') {
                $port = ':' . $server_port;
            }

            $host = '';
            foreach (
                [
                    $this->request->getHeader('X-Forwarded-Host'),
                    $this->request->getServer('HTTP_X_FORWARDED_HOST'),
                    $this->request->getHeader('HOST'),
                    $this->request->getServer('HTTP_HOST'),
                    $this->request->getServer('SERVER_NAME'),
                ] as $v
            ) {
                if ($v) {
                    $host = $v;
                    break;
                }
            }
            if (empty($host)) {
                if ($baseUri = $this->config->path('app.url', '')) {
                    $this->origin = $baseUri;
                    return $this->origin;
                } else {
                    $host = 'localhost';
                }
            }
//            ddd($scheme, $host, $port);

            $this->origin = "{$scheme}://{$host}{$port}/";
        }
        return $this->origin;
    }
}