<?php

namespace Phax\Bridge\Workerman;

use Phax\Bridge\AbstractRequest;


class Request extends AbstractRequest
{
    public \Workerman\Protocols\Http\Request $workerRequest;


    public function __construct(\Workerman\Protocols\Http\Request $request)
    {
        $this->workerRequest = $request;
        $this->queryData = $request->get() ?: [];
        $this->postData = $request->post() ?: [];
        $this->cookieData = $request->cookie() ?: [];
        $this->combinedRequest = array(
            $this->queryData,
            $this->postData,
            $this->cookieData,
        );
    }

    public function getClientAddress(bool $trustForwardedHeader = false): bool|string
    {
        return '';
    }

    public function getMethod(): string
    {
        return $this->workerRequest->method();
    }

    public function getRawBody(): string
    {
        return $this->workerRequest->rawBody();
    }

    public function getServer(string $name): ?string
    {
        return $this->workerRequest->header($name, '');
    }

    public function getFiles(): array
    {
        return $this->workerRequest->file() ?: [];
    }

    public function hasHeader(string $header): bool
    {
        return !!$this->workerRequest->header($header);
    }

    public function hasServer(string $name): bool
    {
        return !!$this->workerRequest->header($name);
    }

    public function getServerArray(): array
    {
        return $this->workerRequest->header();
    }
}