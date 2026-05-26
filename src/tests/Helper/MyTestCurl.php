<?php

namespace Tests\Helper;

use Phax\Utils\MyFileSystem;

/**
 * just for unittest
 * use require_once PATH_TAO996 . 'phar/guzzle.phar'; in production
 */
class MyTestCurl
{
    public $ch; // 不要限定 \CurlHandle，避免 PHP 版本问题
    public array $setting = [];
    public array $info = [];
    public bool $jsonPostData = false;
    public string $pathPrefix = '';
    private string $path = '';

    public function __construct(public string $origin)
    {
    }

    private function request(string $path, array $data, string $method): static
    {
        // 只保存配置，不创建 curl
        $this->setting = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => [
                'Accept: */*',
            ],
            CURLOPT_CUSTOMREQUEST => $method,
        ];

        if (in_array($method, ['GET', 'DELETE']) && !empty($data)) {
            $path .= '?' . http_build_query($data);
        }

        $this->path = $path;

        if (in_array($method, ['POST', 'PUT'])) {
            if ($this->jsonPostData) {
                $this->setting[CURLOPT_POSTFIELDS] = json_encode($data);
                $this->setting[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
            } else {
                $this->setting[CURLOPT_POSTFIELDS] = http_build_query($data);
            }
        }

        return $this;
    }

    public function get(string $path, array $query = []): static
    {
        return $this->request($path, $query, 'GET');
    }

    public function delete(string $path, array $query = []): static
    {
        return $this->request($path, $query, 'DELETE');
    }

    public function post(string $path, array $data = [], bool $dataInQuery = false): static
    {
        if ($dataInQuery && !empty($data)) {
            $query = http_build_query($data);
            $path = str_contains($path, '?') ? $path . '&' . $query : $path . '?' . $query;
            $data = [];
        }
        return $this->request($path, $data, 'POST');
    }

    public function put(string $path, array $data = [], bool $dataInQuery = false): static
    {
        if ($dataInQuery && !empty($data)) {
            $query = http_build_query($data);
            $path = str_contains($path, '?') ? $path . '&' . $query : $path . '?' . $query;
            $data = [];
        }
        return $this->request($path, $data, 'PUT');
    }

    public function cookie(string $cookieJar): static
    {
        $this->setting[CURLOPT_HEADER] = 0;
        $this->setting[CURLOPT_RETURNTRANSFER] = true;
        $this->setting[CURLOPT_COOKIEJAR] = $cookieJar;
        return $this;
    }

    public function setJsonBody(array $data): static
    {
        $this->setting[CURLOPT_POSTFIELDS] = json_encode($data);
        $this->setting[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
        return $this;
    }

    public function addHeader(string $key, string $value): static
    {
        $this->setting[CURLOPT_HTTPHEADER][] = "$key: $value";
        return $this;
    }

    public function beforeSend()
    {
    }

    public function afterClose()
    {
    }

    public function requestURL():string
    {
        return MyFileSystem::concat(
            $this->origin,
            $this->pathPrefix . $this->pathTest($this->path)
        );
    }

    // ==============================
    // 🔥 核心：curl_init 放在这里，创建即稳定
    // ==============================
    public function send(bool $ddd = false): array
    {
        // ✅✅✅ 最重要：发送时才创建 curl
        $this->ch = curl_init();

        $url = $this->requestURL();

        // 基础配置
        $this->setting[CURLOPT_URL] = $url;
        $this->setting[CURLINFO_HEADER_OUT] = true;

        // 本地 HTTPS 配置
        $this->setting[CURLOPT_SSL_VERIFYPEER] = false;
        $this->setting[CURLOPT_SSL_VERIFYHOST] = false;
        $this->setting[CURLOPT_FORBID_REUSE] = true;
        $this->setting[CURLOPT_FRESH_CONNECT] = true;
        $this->setting[CURLOPT_SSL_SESSIONID_CACHE] = false;
        $this->setting[CURLOPT_CONNECTTIMEOUT] = 10;

        // 一次性设置所有配置
        curl_setopt_array($this->ch, $this->setting);

        $this->beforeSend();
        $content = curl_exec($this->ch);
        $error = curl_error($this->ch);
        $this->info = curl_getinfo($this->ch);
        $httpCode = $this->info['http_code'] ?? 0;

        // Windows + Docker 下 SSL_ERROR_SYSCALL 间歇性故障重试
        $maxRetries = 2;
        $attempt = 0;
        while ($content === false
            && $error !== ''
            && $httpCode === 0
            && ++$attempt <= $maxRetries
        ) {
            usleep(200_000); // 200ms
            curl_close($this->ch); // 关闭旧句柄
            $this->ch = curl_init();
            curl_setopt_array($this->ch, $this->setting);
            $this->beforeSend();
            $content = curl_exec($this->ch);
            $error = curl_error($this->ch);
            $this->info = curl_getinfo($this->ch);
            $httpCode = $this->info['http_code'] ?? 0;
        }

        $this->afterClose();
        curl_close($this->ch);
        $this->ch = null; // 销毁句柄

        if ($ddd) {
            ddd($url, $content, $error, $httpCode);
        }

        return [$content, $httpCode ?: 0];
    }

    private function pathTest(string $path): string
    {
        return !str_contains($path, '?') ? $path . '?test=on' : $path . '&test=on';
    }

    public function getRequestHeader()
    {
        return $this->info['request_header'] ?? '';
    }

    public function getError()
    {
        return curl_error($this->ch);
    }
}