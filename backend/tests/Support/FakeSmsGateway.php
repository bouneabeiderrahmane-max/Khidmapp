<?php

namespace Tests\Support;

use App\Contracts\SmsGateway;

class FakeSmsGateway implements SmsGateway
{
    /** @var array<int, array{phone: string, message: string}> */
    public array $sent = [];

    public function send(string $phone, string $message): void
    {
        $this->sent[] = ['phone' => $phone, 'message' => $message];
    }

    public function lastCodeFor(string $phone): ?string
    {
        foreach (array_reverse($this->sent) as $entry) {
            if ($entry['phone'] === $phone && preg_match('/(\d{6})/', $entry['message'], $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}
