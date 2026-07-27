<?php

namespace App\Exceptions\Payment;

use RuntimeException;

class InvalidWebhookSignatureException extends RuntimeException
{
    public static function make(): self
    {
        return new self(__('khidmapp.invalid_webhook_signature'));
    }
}
