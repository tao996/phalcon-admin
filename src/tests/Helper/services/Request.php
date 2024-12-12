<?php

namespace Tests\Helper\services;

class Request implements \Phalcon\Http\RequestInterface
{
    /**
     * @var array 请求的数据
     */
    public array $data = [
        'get' => [],
        'getAcceptableContent' => [],
        'getBasicAuth' => [],
        'getBestAccept' => '',
        'getBestCharset' => '',
        'getBestLanguage' => '',
        'getClientAddress' => '127.0.0.1',
        'getContentType' => '',
        'getDigestAuth' => [],
        'getHeaders' => [],
        'getHttpHost' => '',
        'getHTTPReferer' => '',
        'getJsonRawBody' => [],
        'getLanguages' => [],
        'getMethod' => 'get',
        'getPort' => '80',
        'getURI' => '',
        'getPost' => [],
        'getPut' => [],
        'getQuery' => [],
        'getRawBody' => '',
        'getScheme' => '',
        'getServer' => [],
        'getServerAddress' => '',
        'getServerName' => '',
        'getUploadedFiles' => [],
        'getUserAgent' => '',
        'isAjax' => false,
        'isConnect' => false,
        'isDelete' => false,
        'isGet' => false,
        'isHead' => false,
        'isMethod' => [],
        'isOptions' => false,
        'isPost' => false,
        'isPurge' => false,
        'isPut' => false,
        'isSecure' => false,
        'isSoap' => false,
        'isTrace' => false,
        'numFiles' => 0,
    ];

    public function get(
        string $name = null,
        $filters = null,
        $defaultValue = null,
        bool $notAllowEmpty = false,
        bool $noRecursive = false
    ): mixed {
        if ($name) {
            return $this->data['get'][$name] ?? $defaultValue;
        } else {
            return $this->data['get'];
        }
    }

    public function getAcceptableContent(): array
    {
        return $this->data[__FUNCTION__];
    }

    public function getBasicAuth(): ?array
    {
        return $this->data[__FUNCTION__];
    }

    public function getBestAccept(): string
    {
        return $this->data['bestAccept'];
    }

    public function getBestCharset(): string
    {
        return $this->data['bestCharset'];
    }

    public function getBestLanguage(): string
    {
        return $this->data['bestLanguage'];
    }

    public function getClientAddress(bool $trustForwardedHeader = false): bool|string
    {
        return $this->data['clientAddress'] ?? '127.1.1.1';
    }

    public function getClientCharsets(): array
    {
        return $this->data[__FUNCTION__];
    }

    public function getContentType(): ?string
    {
        return $this->data[__FUNCTION__];
    }

    public function getDigestAuth(): array
    {
        return $this->data[__FUNCTION__];
    }

    public function getHeader(string $header): string
    {
        return $this->data['getHeaders'][$header] ?? '';
    }

    public function getHeaders(): array
    {
        return $this->data['getHeaders'];
    }

    public function getHttpHost(): string
    {
        return $this->data['getHttpHost'];
    }

    public function getHTTPReferer(): string
    {
        return $this->data['getHTTPReferer'];
    }

    public function getJsonRawBody(bool $associative = false): bool|array|\stdClass
    {
        return $this->data['getJsonRawBody'];
    }

    public function getLanguages(): array
    {
        return $this->data['getLanguages'];
    }

    public function getMethod(): string
    {
        return $this->data['getMethod'];
    }

    public function getPort(): int
    {
        return $this->data['getPort'];
    }

    public function getURI(bool $onlyPath = false): string
    {
        return $this->data['getURI'];
    }

    public function getPost(
        string $name = null,
        $filters = null,
        $defaultValue = null,
        bool $notAllowEmpty = false,
        bool $noRecursive = false
    ): mixed {
        if ($name) {
            return $this->data['getPost'][$name] ?? $defaultValue;
        } else {
            return $this->data['getPost'];
        }
    }

    public function getPut(
        string $name = null,
        $filters = null,
        $defaultValue = null,
        bool $notAllowEmpty = false,
        bool $noRecursive = false
    ): mixed {
        if ($name) {
            return $this->data['getPut'][$name] ?? $defaultValue;
        } else {
            return $this->data['getPut'];
        }
    }

    public function getQuery(
        string $name = null,
        $filters = null,
        $defaultValue = null,
        bool $notAllowEmpty = false,
        bool $noRecursive = false
    ): mixed {
        if ($name) {
            return $this->data['getQuery'][$name] ?? $defaultValue;
        } else {
            return $this->data['getQuery'];
        }
    }

    public function getRawBody(): string
    {
        return $this->data['getRawBody'];
    }

    public function getScheme(): string
    {
        return $this->data['getScheme'];
    }

    public function getServer(string $name): ?string
    {
        return $this->data['getServer'][$name] ?? '';
    }

    public function getServerAddress(): string
    {
        return $this->data['getServerAddress'];
    }

    public function getServerName(): string
    {
        return $this->data[__FUNCTION__];
    }

    public function getUploadedFiles(bool $onlySuccessful = false, bool $namedKeys = false): array
    {
        return $this->data[__FUNCTION__];
    }

    public function getUserAgent(): string
    {
        return $this->data[__FUNCTION__];
    }

    public function has(string $name): bool
    {
        return $this->data[__FUNCTION__];
    }

    public function hasFiles(): bool
    {
        return !empty($this->data['getUploadedFiles']);
    }

    public function hasHeader(string $header): bool
    {
        return isset($this->data['getHeaders'][$header]);
    }

    public function hasQuery(string $name): bool
    {
        return isset($this->data['getQuery'][$name]);
    }

    public function hasPost(string $name): bool
    {
        return isset($this->data['getPost'][$name]);
    }

    public function hasPut(string $name): bool
    {
        return isset($this->data['getPut'][$name]);
    }

    public function hasServer(string $name): bool
    {
        return isset($this->data['getServer'][$name]);
    }

    public function isAjax(): bool
    {
        return $this->data[__FUNCTION__];
    }

    public function isConnect(): bool
    {
        return $this->data[__FUNCTION__];
    }

    public function isDelete(): bool
    {
        return $this->data[__FUNCTION__];
    }

    public function isGet(): bool
    {
        return $this->data[__FUNCTION__];
    }

    public function isHead(): bool
    {
        return $this->data[__FUNCTION__];
    }

    public function isMethod($methods, bool $strict = false): bool
    {
        return in_array($methods, $this->data[__FUNCTION__]);
    }

    public function isOptions(): bool
    {
        return $this->data[__FUNCTION__];
    }

    public function isPost(): bool
    {
        return $this->data[__FUNCTION__];
    }

    public function isPurge(): bool
    {
        return $this->data[__FUNCTION__];
    }

    public function isPut(): bool
    {
        return $this->data[__FUNCTION__];
    }

    public function isSecure(): bool
    {
        return $this->data[__FUNCTION__];
    }

    public function isSoap(): bool
    {
        return $this->data[__FUNCTION__];
    }

    public function isTrace(): bool
    {
        return $this->data[__FUNCTION__];
    }

    public function numFiles(bool $onlySuccessful = false): int
    {
        return $this->data[__FUNCTION__];
    }
}