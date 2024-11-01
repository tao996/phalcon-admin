<?php

namespace App\Modules\tao\sdk;

interface SmsDriverInterface
{
    public function engine(): string;

    public function addPhoneNumber(string $phoneNumber): static;

    public function addTemplateCode(string $templateCode): static;

    public function addTemplateParams(array $params): static;

    public function send();

    public function isSendSuccess($response): bool;
}