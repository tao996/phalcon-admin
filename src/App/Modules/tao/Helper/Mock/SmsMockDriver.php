<?php

namespace App\Modules\tao\Helper\Mock;

use App\Modules\tao\sdk\SmsDriverInterface;

readonly class SmsMockDriver implements SmsDriverInterface
{
    public function __construct(private bool $success)
    {
    }

    public function addPhoneNumber(string $phoneNumber): static
    {
        return $this;
    }

    public function addTemplateCode(string $templateCode): static
    {
        return $this;
    }

    public function addTemplateParams(array $params): static
    {
        return $this;
    }

    public function send()
    {
        return [];
    }

    public function isSendSuccess($response): bool
    {
        return $this->success;
    }

    public function engine(): string
    {
        return 'mock_sms';
    }
}