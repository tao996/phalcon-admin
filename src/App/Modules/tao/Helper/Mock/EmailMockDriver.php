<?php

namespace App\Modules\tao\Helper\Mock;

use App\Modules\tao\sdk\EmailDriverInterface;

readonly class EmailMockDriver implements EmailDriverInterface
{


    public function __construct(private bool $success)
    {

    }

    public function setSubject(string $subject): static
    {
        return $this;
    }

    public function setAddress(array|string $address): static
    {
        return $this;
    }

    public function setHtmlBody(string $html): static
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

    public function useSingleSendMailRequest(): static
    {
        return $this;
    }

    public function useBatchSendMailRequest(): static
    {
        return $this;
    }

    public function engine(): string
    {
        return 'mock_email';
    }
}