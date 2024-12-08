<?php

namespace Tests\Helper;


class MyTestHttpHelper
{
    public static string $origin = '';
    protected MyTestCurl $myCurl;
    public int $httpCode = 0;
    public string $content = '';


    public function __construct(public \PHPUnit\Framework\TestCase $tc)
    {
//        ddd($_ENV);
        $this->myCurl = new MyTestCurl(self::$origin ?: env('APP_NAME') . '-nginx');
        $this->afterConstruct();
    }

    public function getOrigin(): string
    {
        return $this->myCurl->origin;
    }

    public function afterConstruct(): void
    {
        // do some job for MyTestCurl
    }

    public static function with(\PHPUnit\Framework\TestCase $tc): MyTestHttpHelper
    {
        return new MyTestHttpHelper($tc);
    }

    /**
     * 使用 CLI（workerman/swoole） 服务器地址
     * @return $this
     */
    public function cliServer(): static
    {
        $this->myCurl->origin = env('APP_NAME') . '-cli:' . env('WS_PORT');
        return $this;
    }

    /**
     * 发起一个 HTTP GET 请求
     * copy from https://www.php.net/manual/zh/book.curl.php#116122
     * @param string $path
     * @return MyTestHttpHelper
     */
    public function get(string $path, array $query = []): static
    {
        $this->myCurl->get($path, $query);
        return $this;
    }

    public function delete(string $path, array $query = []): static
    {
        $this->myCurl->delete($path, $query);
        return $this;
    }

    public function post(string $path, array $data = [], bool $dataInQuery = false): static
    {
        $this->myCurl->post($path, $data, $dataInQuery);
        return $this;
    }

    public function put(string $path, array $data = [], bool $dataInQuery = false): static
    {
        $this->myCurl->put($path, $data, $dataInQuery);
        return $this;
    }

    /**
     * 使用 cookie
     * @return $this
     */
    public function cookie(): static
    {
        $this->myCurl->cookie(__DIR__ . '/cookie.txt');
        return $this;
    }

    /**
     * 发送请求
     * @return $this
     */
    public function send(): static
    {
        list($this->content, $this->httpCode) = $this->myCurl->send();
        return $this;
    }

    public function getInfo(): array
    {
        return $this->myCurl->info;
    }

    /**
     * 断言状态码
     * @param int $httpCode
     * @return $this
     */
    public function assertHttpCode(int $httpCode): static
    {
        $this->tc->assertEquals($httpCode, $this->httpCode);
        return $this;
    }

    /**
     * 返回的内容必须与指定的内容完全一致
     * @param string $content
     * @return $this
     */
    public function assertContent(string $content): static
    {
        $this->tc->assertEquals($content, $this->content);
        return $this;
    }

    /**
     * 返回的内容中必须包含指定的字符串
     * @param array|string $texts
     * @return $this
     */
    public function contains(array|string $texts): static
    {
        if (is_string($texts)) {
            $texts = [$texts];
        }
        foreach ($texts as $text) {
            $this->tc->assertStringContainsString($text, $this->content, 'could not find text: ' . $text);
        }
        return $this;
    }

    /**
     * 返回的内容必须包含字符串中的任意一个
     * @param array $texts
     * @return $this
     */
    public function orContains(array $texts): static
    {
        foreach ($texts as $text) {
            if (str_contains($this->content, $text)) {
                return $this;
            }
        }
        $this->tc->fail('could not find any text: ' . implode(',', $texts));
    }

    /**
     * 返回的内容中不出现错误信息
     * @return $this
     */
    public function notContainsFailed(): static
    {
        foreach (
            [
                'Warning: Undefined variable',
                'Call Stack',
                '.../Application.php',
                'Fatal error:',
                'Error: '
            ] as $text
        ) {
            $this->tc->assertStringNotContainsString($text, $this->content, 'should not found text: ' . $text);
        }
        return $this;
    }

    /**
     * 返回的内容为 json 格式数据
     * @return array
     */
    public function jsonResponse(): array
    {
        if (!json_validate($this->content)) {
            ddd('not json response', strip_tags($this->content));
        }
        return json_decode($this->content, true);
    }

    public function jsonResponseData(): mixed
    {
        $response = $this->jsonResponse();
        if (!empty($response['msg'])) {
            ddd('jsonResponse failed', $response);
        }
        return $response['data'];
    }

    public function testJsonPaginationResponse(): array
    {
        $response = $this->jsonResponse();
        $this->tc->assertTrue($response['data']['count'] >= 0);
        $this->tc->assertTrue(isset($response['data']['rows']));
        return $response;
    }

    public function testResponseCode0(): array
    {
        $response = $this->jsonResponse();
        $this->tc->assertEquals(0, $response['code']);
        return $response;
    }

    public function testModelSaveResponse(bool $data = true): array
    {
        $response = $this->testResponseCode0();
        $this->tc->assertTrue($response['data']['id'] > 0);
        return $data ? $response['data'] : $response;
    }

    /**
     * 返回的内容不要包含指定的字符串
     * @param array $texts
     * @return $this
     */
    public function notContains(array $texts = []): static
    {
        foreach ($texts as $text) {
            $this->tc->assertStringNotContainsString($text, $this->content, 'should not found text: ' . $text);
        }
        return $this;
    }

}