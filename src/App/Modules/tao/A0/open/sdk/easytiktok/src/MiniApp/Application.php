<?php

namespace EasyTiktok\MiniApp;


use EasyWeChat\Kernel\Contracts\AccessToken as AccessTokenInterface;
use EasyTiktok\Kernel\HttpClient;

class Application extends \EasyWeChat\MiniApp\Application
{
    public function getAccessToken(): AccessTokenInterface
    {
        if (!$this->accessToken) {
            $this->accessToken = new AccessToken(
                appId: $this->getAccount()->getAppId(),
                secret: $this->getAccount()->getSecret(),
                cache: $this->getCache(),
                httpClient: HttpClient::create(HttpClient::sandbox($this->config)),
                stable: false
            );
        }
        return $this->accessToken;
    }

    protected function getHttpClientDefaultOptions(): array
    {
        return array_merge(['base_uri' => HttpClient::defaultBaseUri(HttpClient::sandbox($this->config))],
            (array)$this->config->get('http', []));
    }
}