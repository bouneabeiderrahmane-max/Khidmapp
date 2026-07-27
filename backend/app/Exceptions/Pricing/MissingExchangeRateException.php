<?php

namespace App\Exceptions\Pricing;

use RuntimeException;

class MissingExchangeRateException extends RuntimeException
{
    public static function forPair(string $currencyPair): self
    {
        return new self("Aucun taux de change configuré pour {$currencyPair}.");
    }
}
