<?php

namespace App\Exceptions\Pricing;

use RuntimeException;

class MissingDeliveryFeeTierException extends RuntimeException
{
    public static function forZone(string $zone, float $amount): self
    {
        return new self("Aucune grille de frais de livraison ne couvre {$amount} MRU pour la zone \"{$zone}\".");
    }
}
