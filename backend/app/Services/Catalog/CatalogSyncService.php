<?php

namespace App\Services\Catalog;

use App\Contracts\CatalogFetcher;
use App\Contracts\TranslatorGateway;
use App\DataTransferObjects\Catalog\RawCatalogProduct;
use App\Models\Boutique;
use App\Models\CategoryMapping;
use App\Models\Product;
use App\Models\SyncLog;
use App\Support\ProductStatus;
use App\Support\SyncLogStatus;
use Throwable;

/**
 * Orchestration d'un cycle de synchronisation catalogue (8.2 du CDC).
 * La récupération réelle des données est déléguée à CatalogFetcher (voir
 * StubCatalogFetcher — pas un vrai scraper pour l'instant) ; cette classe se
 * concentre sur l'upsert produits/variantes, la détection d'anomalies
 * (8.2.2) et la journalisation (8.2.3).
 */
class CatalogSyncService
{
    private const DEFAULT_UNAVAILABLE_GRACE_DAYS = 14;

    public function __construct(
        private readonly CatalogFetcher $fetcher,
        private readonly TranslatorGateway $translator,
    ) {}

    public function sync(Boutique $boutique): SyncLog
    {
        $log = SyncLog::query()->create([
            'boutique_id' => $boutique->id,
            'status' => SyncLogStatus::RUNNING,
            'started_at' => now(),
        ]);

        try {
            [$created, $updated, $seenRefs] = $this->upsertFromSource($boutique);
            $disabled = $this->markMissingProductsUnavailable($boutique, $seenRefs);
            $this->discontinueLongUnavailableProducts($boutique);

            $log->update([
                'status' => SyncLogStatus::SUCCESS,
                'finished_at' => now(),
                'products_created' => $created,
                'products_updated' => $updated,
                'products_disabled' => $disabled,
            ]);
        } catch (Throwable $e) {
            // Règle 8.2.2 : en cas d'échec, on conserve le dernier catalogue
            // valide connu (on ne touche à aucun produit existant) et on se
            // contente de journaliser l'erreur. La notification à l'équipe
            // technique fera partie du module Notifications (Sprint 9).
            $log->update([
                'status' => SyncLogStatus::FAILED,
                'finished_at' => now(),
                'errors' => [$e->getMessage()],
            ]);
        }

        return $log;
    }

    /**
     * @return array{0: int, 1: int, 2: array<string>}
     */
    private function upsertFromSource(Boutique $boutique): array
    {
        $created = 0;
        $updated = 0;
        $seenRefs = [];

        foreach ($this->fetcher->fetch($boutique) as $raw) {
            $seenRefs[] = $raw->externalRef;

            $existed = Product::query()
                ->where('boutique_id', $boutique->id)
                ->where('external_ref', $raw->externalRef)
                ->exists();

            $this->upsertProduct($boutique, $raw);

            $existed ? $updated++ : $created++;
        }

        return [$created, $updated, $seenRefs];
    }

    private function upsertProduct(Boutique $boutique, RawCatalogProduct $raw): Product
    {
        $categoryId = CategoryMapping::query()->firstOrCreate([
            'boutique_id' => $boutique->id,
            'source_category_ref' => $raw->sourceCategoryRef,
        ])->category_id;

        $existing = Product::query()
            ->where('boutique_id', $boutique->id)
            ->where('external_ref', $raw->externalRef)
            ->first();

        $lowestPrice = min(array_map(fn ($variant) => $variant->priceEur, $raw->variants));

        $attributes = [
            'category_id' => $categoryId,
            'images' => $raw->images,
            'base_price_eur' => $lowestPrice,
            'status' => ProductStatus::ACTIVE,
            'source_url' => $raw->sourceUrl,
            'last_synced_at' => now(),
            'unavailable_since' => null,
        ];

        // 8.2.1 : une correction manuelle de traduction ne doit pas être
        // écrasée par la traduction automatique au cycle suivant.
        if ($existing === null || ! $existing->translation_locked) {
            $attributes['name'] = $this->translator->translate($raw->name);
            $attributes['description'] = $raw->description !== null
                ? $this->translator->translate($raw->description)
                : null;
        }

        $product = Product::query()->updateOrCreate(
            ['boutique_id' => $boutique->id, 'external_ref' => $raw->externalRef],
            $attributes
        );

        foreach ($raw->variants as $variant) {
            $product->variants()->updateOrCreate(
                ['external_variant_ref' => $variant->externalVariantRef],
                [
                    'sku' => $variant->sku,
                    'size' => $variant->size,
                    'color' => $variant->color,
                    'price_eur' => $variant->priceEur,
                    'stock_status' => $variant->inStock ? 'in_stock' : 'out_of_stock',
                ]
            );
        }

        return $product;
    }

    /**
     * Un produit actif non revu lors de ce cycle est marqué indisponible
     * (8.2.2).
     *
     * @param  array<string>  $seenRefs
     */
    private function markMissingProductsUnavailable(Boutique $boutique, array $seenRefs): int
    {
        $query = Product::query()
            ->where('boutique_id', $boutique->id)
            ->where('status', ProductStatus::ACTIVE);

        if ($seenRefs !== []) {
            $query->whereNotIn('external_ref', $seenRefs);
        }

        $missing = $query->get();

        foreach ($missing as $product) {
            $product->update([
                'status' => ProductStatus::INDISPONIBLE,
                'unavailable_since' => now(),
            ]);
        }

        return $missing->count();
    }

    /**
     * Retire du catalogue actif (discontinued) un produit indisponible
     * depuis plus longtemps que le délai configuré pour la boutique (8.2.2 :
     * "retiré du catalogue actif après un délai configurable").
     */
    private function discontinueLongUnavailableProducts(Boutique $boutique): void
    {
        $graceDays = $boutique->sync_config['unavailable_grace_days'] ?? self::DEFAULT_UNAVAILABLE_GRACE_DAYS;

        Product::query()
            ->where('boutique_id', $boutique->id)
            ->where('status', ProductStatus::INDISPONIBLE)
            ->whereNotNull('unavailable_since')
            ->where('unavailable_since', '<=', now()->subDays($graceDays))
            ->update(['status' => ProductStatus::DISCONTINUED]);
    }
}
