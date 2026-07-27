<?php

namespace App\Exceptions\Payment;

use RuntimeException;

class InvalidPaymentAttemptException extends RuntimeException
{
    public static function orderNotAwaitingPayment(): self
    {
        return new self(__('khidmapp.order_not_awaiting_payment'));
    }

    public static function wrongMethod(string $expectedMethod): self
    {
        return new self(__('khidmapp.payment_method_mismatch', ['method' => __('khidmapp.payment_method.'.$expectedMethod)]));
    }

    public static function proofAlreadyReviewed(): self
    {
        return new self(__('khidmapp.payment_proof_already_reviewed'));
    }
}
