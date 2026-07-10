<?php

namespace App\Modules\tao\Helper;

use App\Modules\tao\Services\ConfigService;

class RegisterHelper
{
    public array $cache = [];

    public function __construct()
    {
        $this->cache = ConfigService::groupRows('oauth');
    }


    public function accountPlaceholder(): string
    {
        $messages = [];
        if ($this->supportCnPhone()){
            $messages[] = '手机号码';
        }
        if ($this->supportEmail()){
            $messages[] = '电子邮箱';
        }
        return join('/',$messages);
    }

    /**
     * 是否支持 +86 手机号注册登录
     * @return bool
     */
    public function supportCnPhone(): bool
    {
        return ConfigService::activeValue($this->cache['cn_phone']);
    }

    /**
     * 是否支持邮箱注册登录
     * @return bool
     */
    public function supportEmail(): bool
    {
        return ConfigService::activeValue($this->cache['email']);
    }

    /**
     * 是否开放注册功能
     * @return bool
     */
    public function supportRegister(): bool
    {
        return ConfigService::activeValue($this->cache['register']);
    }

    /**
     * 是否支持 google 登录
     * @return bool
     */
    public function supportGoogle(): bool
    {
        return ConfigService::activeValue($this->cache['google_oauth']);
    }

    public function googleProvider(): array
    {
        return [
            'enabled' => $this->supportGoogle(),
            'keys' => [
                'id' => $this->cache['google_client_id'],
                'secret' => $this->cache['google_client_secret']
            ]
        ];
    }
}