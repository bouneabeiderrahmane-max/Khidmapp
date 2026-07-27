<?php

namespace App\Exceptions\Order;

use RuntimeException;

class InvalidOrderTransitionException extends RuntimeException
{
    public static function from(string $from, string $to): self
    {
        return new self("Transition de statut invalide : \"{$from}\" → \"{$to}\".");
    }
}
