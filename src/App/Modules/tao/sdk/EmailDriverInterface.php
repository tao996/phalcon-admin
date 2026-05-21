<?php

namespace App\Modules\tao\sdk;

interface EmailDriverInterface
{
    public function engine(): string;

    public function setSubject(string $subject): static;

    public function setAddress(string|array $address): static;

    public function setHtmlBody(string $html): static;

    public function send();

    public function isSendSuccess($response): bool;

    public function useSingleSendMailRequest(): static;

    public function useBatchSendMailRequest(): static;

}