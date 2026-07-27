<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Services\Pricing\PricingService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Recherche du catalogue public (CDC 7.2.2) : mot-clé, boutique, catégorie,
 * couleur, taille, fourchette de prix, tri.
 *
 * Le prix final (MRU) est calculé par produit via PricingService puis
 * utilisé pour le filtre de prix et le tri — ce qui ne peut pas se faire
 * au niveau SQL puisqu'il dépend de règles de marge/livraison résolues en
 * PHP. La liste filtrée par les autres critères est donc chargée en
 * mémoire avant ce filtrage/tri par prix. Pour la taille du catalogue de
 * cette session (quelques dizaines de produits de démonstration), c'est
 * largement suffisant ; à revoir (ex. dénormaliser un prix final en base,
 * recalculé à chaque changement de taux/marge) si le catalogue grossit
 * significativement.
 */
class ProductSearchService
{
    public function __construct(private readonly PricingService $pricing) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters): LengthAwarePaginatorContract
    {
        $query = Product::query()
            ->active()
            ->whereHas('boutique', fn ($q) => $q->active())
            ->with(['boutique', 'category', 'variants']);

        if (! empty($filters['boutique'])) {
            $query->whereHas('boutique', fn ($q) => $q->where('slug', $filters['boutique']));
        }

        if (! empty($filters['category'])) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $filters['category']));
        }

        if (! empty($filters['color']) || ! empty($filters['size'])) {
            $query->whereHas('variants', function ($q) use ($filters) {
                $q->where('stock_status', 'in_stock');

                if (! empty($filters['color'])) {
                    $q->where('color', $filters['color']);
                }

                if (! empty($filters['size'])) {
                    $q->where('size', $filters['size']);
                }
            });
        }

        $products = $query->get();

        // Filtré en mémoire plutôt qu'en SQL : `name` est un JSON bilingue
        // et une recherche insensible à la casse portable entre PostgreSQL
        // (production) et SQLite (tests) sur un champ JSON est plus simple
        // à exprimer ainsi qu'avec du SQL spécifique à chaque moteur.
        if (! empty($filters['q'])) {
            $keyword = Str::lower($filters['q']);
            $products = $products->filter(
                fn (Product $p) => str_contains(Str::lower($p->name['fr'] ?? ''), $keyword)
                    || str_contains(Str::lower($p->name['ar'] ?? ''), $keyword)
            );
        }

        $priced = $this->priceEligibleProducts($products);

        if (isset($filters['min_price']) || isset($filters['max_price'])) {
            $min = (float) ($filters['min_price'] ?? 0);
            $max = (float) ($filters['max_price'] ?? INF);
            $priced = $priced->filter(fn (Product $p) => $p->price_from_mru >= $min && $p->price_from_mru <= $max);
        }

        $priced = (match ($filters['sort'] ?? 'newest') {
            'price_asc' => $priced->sortBy('price_from_mru'),
            'price_desc' => $priced->sortByDesc('price_from_mru'),
            default => $priced->sortByDesc('created_at'),
        })->values();

        $perPage = min((int) ($filters['per_page'] ?? config('catalog.default_per_page')), config('catalog.max_per_page'));
        $page = (int) ($filters['page'] ?? 1);

        return new LengthAwarePaginator(
            $priced->forPage($page, $perPage)->values(),
            $priced->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    /**
     * Calcule et attache le prix final (variante la moins chère disponible)
     * à chaque produit ; écarte les produits sans aucune variante utilisable
     * (ne devrait pas arriver pour un produit "active" issu de la synchro).
     *
     * @param  Collection<int, Product>  $products
     * @return Collection<int, Product>
     */
    private function priceEligibleProducts(Collection $products): Collection
    {
        return $products
            ->map(function (Product $product) {
                $variant = $product->variants->firstWhere('stock_status', 'in_stock') ?? $product->variants->first();

                if ($variant === null) {
                    return null;
                }

                $breakdown = $this->pricing->priceForVariant($variant);
                $product->setAttribute('price_from_mru', $breakdown->finalPriceMru);
                $product->setAttribute('priced_variant_id', $variant->id);

                return $product;
            })
            ->filter();
    }
}
