<?php

namespace App\Modules\tao\Helper;

use App\Modules\tao\sdk\phaxui\helper\Html;

class RegisterHelper
{
    public array $cache = [];

    public function __construct(public MyMvcHelper $mvc)
    {
        $this->cache = $this->mvc->configService()->groupRows('oauth');
    }


    public function accountPlaceholder(): string
    {
        return Html::placeholderMerge([
            '手机号' => $this->supportCnPhone(),
            '电子邮箱' => $this->supportEmail(),
        ]);
    }

    public function supportCnPhone(): bool
    {
        return $this->mvc->configService()->activeValue($this->cache['cn_phone']);
    }

    public function supportEmail(): bool
    {
        return $this->mvc->configService()->activeValue($this->cache['email']);
    }

    public function supportRegister(): bool
    {
        return $this->mvc->configService()->activeValue($this->cache['register']);
    }

    public function supportGoogle(): bool
    {
        return $this->mvc->configService()->activeValue($this->cache['google_oauth']);
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