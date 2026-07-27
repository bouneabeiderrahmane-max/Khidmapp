<?php

namespace App\Exceptions\Order;

use RuntimeException;

class InvalidOrderTransitionException extends RuntimeException
{
    public static function from(string $from, string $to): self
    {
        return new self(__('khidmapp.invalid_order_transition', ['from' => $from, 'to' => $to]));
    }
}
