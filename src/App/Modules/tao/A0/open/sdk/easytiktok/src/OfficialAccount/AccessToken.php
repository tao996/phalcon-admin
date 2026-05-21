<?php

namespace EasyTiktok\OfficialAccount;

use Phax\Support\Logger;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AccessToken extends \EasyWeChat\OfficialAccount\AccessToken
{
    public function __construct(string $appId, string $secret, ?string $key = null, CacheInterface $cache = null, HttpClientInterface $httpClient = null, ?bool $stable = false)
    {
        $cache = $cache ?? new Psr16Cache(new FilesystemAdapter('easytiktok', 1500));
        parent::__construct($appId, $secret, $key, $cache,
            $httpClient ?? HttpClient::create(['base_uri' => 'https://open.douyin.com/'])
            , $stable);
    }

    public function getStableAccessToken(bool $force_refresh = false): string
    {
        logger()->warning('暂不支持 tiktok.getStableAccessToken 方法' . __CLASS__);
        return $this->getAccessToken();
    }

    /**
     * @link https://developer.open-douyin.com/docs/resource/zh-CN/mini-app/develop/server/interface-request-credential/non-user-authorization/get-client_token
     * @return string
     */
    public function getAccessToken(): string
    {
        $response = $this->httpClient->request('POST', 'oauth/client_token/', [
            'json' => [
                'grant_type' => 'client_credential',
                'client_key' => $this->appId,
                'client_secret' => $this->secret
            ]
        ])->toArray();
        if (!isset($response['data'])) {
            throw new \Exception('tiktok getClientToken 响应格式错误');
        }
        $data = $response['data'];
        if (empty($data['access_token'])) {
            Logger::error('tiktok 获取应用授权调用凭证错误',$response);
            throw new \Exception('Failed to get client_token:'.$data['description']);
        }

        $this->cache->set($this->getKey(), $data['access_token'], intval($data['expires_in']));

        return $data['access_token'];
    }


}