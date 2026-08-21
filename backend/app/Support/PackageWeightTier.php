<?php

namespace App\Support;

/**
 * Poids de colis choisi par le client lui-même au moment du paiement
 * (comme dans l'app de référence "achat par proxy" dont des captures ont
 * été fournies) — une estimation déclarative, pas un poids réel mesuré
 * (impossible à connaître avant l'achat effectif pour une commande
 * personnalisée). Tarifs fournis par l'utilisateur, valables uniquement
 * pour la zone Nouakchott — voir PricingService::deliveryFee().
 */
final class PackageWeightTier
{
    public const PETIT = 'petit';

    public const MOYEN = 'moyen';

    public const TRES_GRAND = 'tres_grand';

    private const EXTRA_KG_THRESHOLD = 15.0;

    private const EXTRA_KG_FEE_MRU = 200.0;

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [self::PETIT, self::MOYEN, self::TRES_GRAND];
    }

    /**
     * Seul le palier "très grand paquet" (7-15 kg) accepte un poids
     * supplémentaire au-delà de 15 kg, facturé 200 MRU/kg.
     */
    public static function acceptsExtraWeight(string $tier): bool
    {
        return $tier === self::TRES_GRAND;
    }

    public static function feeMru(string $tier, float $extraWeightKg = 0.0): float
    {
        $baseFee = match ($tier) {
            self::PETIT => 600.0,
            self::MOYEN => 1200.0,
            self::TRES_GRAND => 1700.0,
            default => throw new \InvalidArgumentException("Unknown package weight tier: {$tier}"),
        };

        $extraFee = self::acceptsExtraWeight($tier) ? round($extraWeightKg, 2) * self::EXTRA_KG_FEE_MRU : 0.0;

        return round($baseFee + $extraFee, 2);
    }
}
