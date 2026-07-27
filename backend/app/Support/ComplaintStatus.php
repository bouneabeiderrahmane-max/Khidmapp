<?php

namespace App\Support;

/**
 * Cycle de statuts de réclamation confirmé par le cahier des charges
 * (8.8) : Ouverte, En cours de traitement, En attente client, Résolue,
 * Clôturée. Le CDC ne fournit pas de table de transitions explicite
 * (contrairement aux 15 statuts de commande, 8.4) — les règles ci-dessous
 * sont une interprétation raisonnable du texte ("prise en charge, suivi,
 * clôture" — 7.3), à confirmer si besoin.
 */
final class ComplaintStatus
{
    public const OUVERTE = 'ouverte';

    public const EN_COURS = 'en_cours';

    public const EN_ATTENTE_CLIENT = 'en_attente_client';

    public const RESOLUE = 'resolue';

    public const CLOTUREE = 'cloturee';

    private const TRANSITIONS = [
        self::OUVERTE => [self::EN_COURS, self::CLOTUREE],
        self::EN_COURS => [self::EN_ATTENTE_CLIENT, self::RESOLUE, self::CLOTUREE],
        self::EN_ATTENTE_CLIENT => [self::EN_COURS, self::RESOLUE, self::CLOTUREE],
        self::RESOLUE => [self::CLOTUREE],
        self::CLOTUREE => [],
    ];

    public static function all(): array
    {
        return [self::OUVERTE, self::EN_COURS, self::EN_ATTENTE_CLIENT, self::RESOLUE, self::CLOTUREE];
    }

    public static function allowedNextStatuses(string $status): array
    {
        return self::TRANSITIONS[$status] ?? [];
    }

    public static function isTerminal(string $status): bool
    {
        return $status === self::CLOTUREE;
    }
}
