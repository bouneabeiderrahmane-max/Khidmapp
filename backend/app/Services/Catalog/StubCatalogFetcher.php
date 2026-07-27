<?php

namespace App\Services\Catalog;

use App\Contracts\CatalogFetcher;
use App\DataTransferObjects\Catalog\RawCatalogProduct;
use App\DataTransferObjects\Catalog\RawCatalogVariant;
use App\Models\Boutique;

/**
 * Placeholder : ne fait AUCUNE requête réseau réelle vers un site boutique.
 *
 * Le cahier des charges précise (section 3.3) que les boutiques partenaires
 * ne proposent pas d'API officielle et que la récupération du catalogue
 * devra s'appuyer sur une lecture structurée des pages publiques de chaque
 * site, dans le respect de leurs conditions d'utilisation. Écrire un vrai
 * scraper par boutique (Zara, Mango, Bershka, ...) est un chantier à part
 * entière — structure HTML propre à chaque site, mesures anti-bot,
 * vérification des CGU — qui n'a pas été fait dans cette session.
 *
 * Cette implémentation génère un petit jeu de produits fictifs déterministe
 * par boutique, uniquement pour permettre au moteur de synchronisation
 * (upsert, détection d'anomalies, journalisation) d'être développé et testé
 * de bout en bout. Remplacer par une vraie implémentation de CatalogFetcher
 * par boutique (ou un connecteur tiers) avant toute mise en production.
 */
class StubCatalogFetcher implements CatalogFetcher
{
    public function fetch(Boutique $boutique): iterable
    {
        foreach (range(1, 3) as $i) {
            $ref = "{$boutique->slug}-demo-{$i}";

            yield new RawCatalogProduct(
                externalRef: $ref,
                name: "Article démo {$i} — {$boutique->name}",
                description: 'Produit fictif généré par StubCatalogFetcher (voir docblock).',
                images: ["https://example.com/{$ref}.jpg"],
                sourceCategoryRef: $i === 1 ? 'ropa-mujer' : 'ropa-hombre',
                sourceUrl: "{$boutique->base_url}produit/{$ref}",
                variants: [
                    new RawCatalogVariant(
                        externalVariantRef: "{$ref}-s",
                        sku: strtoupper($ref).'-S',
                        size: 'S',
                        color: null,
                        priceEur: 19.99 + $i,
                    ),
                    new RawCatalogVariant(
                        externalVariantRef: "{$ref}-m",
                        sku: strtoupper($ref).'-M',
                        size: 'M',
                        color: null,
                        priceEur: 19.99 + $i,
                    ),
                ],
            );
        }
    }
}
