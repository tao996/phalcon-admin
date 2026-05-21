<?php

namespace Tests\Helper;


use Phax\Utils\MyFileSystem;

/**
 * just for unittest
 * use require_once PATH_TAO996 . 'phar/guzzle.phar'; in production
 */
class MyTestCurl
{
    public \CurlHandle $ch;
    public array $setting = [];
    public array $info = []; // 保存请求信息
    /**
     * @var bool 将请求的数据作为 json 发送
     */
    public bool $jsonPostData = false;
    public string $pathPrefix = ''; // 请求路径前缀

    public function __construct(public string $origin)
    {
    }

    /**
     * 发送 GET 请求
     * @param string $path 请求路径
     * @param array $query 请求参数
     * @return $this
     */
    public function get(string $path, array $query = []): static
    {
        return $this->request($path, $query, 'GET');
    }

    public function delete(string $path, array $query = []): static
    {
        return $this->request($path, $query, 'DELETE');
    }

    /**
     * 发送 POST 请求
     * @param string $path 请求路径
     * @param array $data 请求参数
     * @return $this
     */
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

    /**
     * 使用 cookie
     * @param string $cookieJar 文件绝对路径
     * @return $this
     */
    public function cookie(string $cookieJar): static
    {
        $this->setting[CURLOPT_HEADER] = 0;
        $this->setting[CURLOPT_RETURNTRANSFER] = true;
        $this->setting[CURLOPT_COOKIEJAR] = $cookieJar;
        return $this;
    }

    private string $path = '';

    private function request(string $path, array $data, string $method): static
    {
        $curl = curl_init();
        $this->setting = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => array(
                'Accept: */*',
            ),
            CURLOPT_CUSTOMREQUEST => $method,
        );

        if (in_array($method, ['GET', 'DELETE']) && !empty($data)) {
            $path .= ('?' . http_build_query($data));
        }
        $this->path = $path;

        if (in_array($method, ['POST', 'PUT'])) {
            if ($this->jsonPostData) {
                $this->setting[CURLOPT_POSTFIELDS] = json_encode($data);
                $this->setting[CURLOPT_HTTPHEADER][] = 'Content-Type:application/json';
            } else {
                $this->setting[CURLOPT_POSTFIELDS] = http_build_query($data);
            }
        }

        $this->ch = $curl;
        return $this;
    }

    public function setJsonBody(array $data): static
    {
        if (empty($this->setting)) {
            throw new \Exception('setJsonBody should be add after request[get/post]');
        }
        $this->setting[CURLOPT_POSTFIELDS] = json_encode($data);
        $this->setting[CURLOPT_HTTPHEADER][] = 'Content-Type:application/json';
        return $this;
    }

    public function addHeader(string $key, string $value): static
    {
        if (empty($this->setting)) {
            throw new \Exception('addHeader should be add after request[get/post]');
        }
        if (isset($this->setting[CURLOPT_HTTPHEADER])) {
            $this->setting[CURLOPT_HTTPHEADER] = array_merge($this->setting[CURLOPT_HTTPHEADER], [
                $key . ':' . $value
            ]);
        } else {
            $this->setting[CURLOPT_HTTPHEADER] = [
                $key . ':' . $value
            ];
        }
        return $this;
    }

    public function beforeSend()
    {
    }

    public function afterClose()
    {
    }

    /**
     * 发送请求，返回响应内容和响应码
     * @return array{string,int}
     */
    public function send(): array
    {
        // https://stackoverflow.com/questions/17092677/how-to-get-info-on-sent-php-curl-request
        // 保存请求信息
        $this->setting[CURLOPT_URL] = MyFileSystem::fullpath(
            $this->origin,
            $this->pathPrefix . $this->pathTest($this->path)
        );

//        ddd($this->setting[CURLOPT_URL]);
        $this->setting[CURLINFO_HEADER_OUT] = true;
        curl_setopt_array($this->ch, $this->setting);
        $this->beforeSend();
        $content = curl_exec($this->ch);

        // CURLINFO_HTTP_CODE
        $data = [$content, $this->info['http_code'] ?? 0];
        $this->afterClose();;
        curl_close($this->ch);
        return $data;
    }


    private function pathTest(string $path): string
    {
        return !str_contains($path, '?') ? $path . '?test=on' : $path . '&test=on';
    }

    /**
     * 发送的请求头信息，可用于调试
     * @return mixed
     */
    public function getRequestHeader()
    {
        return $this->info['request_header'];
    }
}