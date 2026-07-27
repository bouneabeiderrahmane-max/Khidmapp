<?php

namespace App\Services\Reporting;

use App\Models\Order;
use App\Models\OrderItem;
use App\Support\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Tableau de bord & reporting (CDC 8.9.7 + section 12) : chiffre
 * d'affaires, panier moyen, marge réalisée, ventilations boutique/zone/
 * produit, funnel de commandes.
 *
 * Périmètre volontairement limité aux données réellement suivies par le
 * système : aucun coût logistique (transport 3PL, entrepôt Madrid,
 * douane) n'est enregistré nulle part dans l'application, donc aucune
 * "commission" ou marge nette après coûts ne peut être calculée ici — le
 * chiffre produit est une marge brute (majoration commerciale appliquée
 * au prix boutique), pas un résultat net. Signalé explicitement, ne pas
 * présenter le champ margin_realized_mru comme un profit net.
 *
 * Définition retenue pour le chiffre d'affaires (CA) : somme de
 * total_mru pour toutes les commandes hors statut "annulée" (aucune
 * transaction n'a eu lieu) ; les commandes "remboursées" restent
 * incluses car la vente et la livraison ont bien eu lieu. Ce périmètre
 * sert aussi de dénominateur au panier moyen et à la marge réalisée,
 * pour rester cohérent d'une métrique à l'autre.
 */
class DashboardReportService
{
    private const TOP_PRODUCTS_LIMIT = 10;

    /**
     * @param  array{from?: string, to?: string, boutique_id?: int, zone?: string}  $filters
     * @return array<string, mixed>
     */
    public function generate(array $filters = []): array
    {
        $baseQuery = $this->scopedOrders($filters);

        $orderCount = (int) (clone $baseQuery)->count();
        $caMru = (float) (clone $baseQuery)->sum('total_mru');
        $averageBasketMru = $orderCount > 0 ? round($caMru / $orderCount, 2) : 0.0;

        return [
            'period' => [
                'from' => $filters['from'] ?? null,
                'to' => $filters['to'] ?? null,
            ],
            'filters' => [
                'boutique_id' => $filters['boutique_id'] ?? null,
                'zone' => $filters['zone'] ?? null,
            ],
            'ca_mru' => round($caMru, 2),
            'order_count' => $orderCount,
            'average_basket_mru' => $averageBasketMru,
            'margin_realized_mru' => $this->marginRealized($filters),
            'sales_by_boutique' => $this->salesByBoutique($filters),
            'sales_by_zone' => $this->salesByZone($filters),
            'top_products' => $this->topProducts($filters),
            'orders_in_progress' => (clone $baseQuery)->whereNotIn('status', [OrderStatus::DELIVERED, OrderStatus::CANCELLED, OrderStatus::REFUNDED])->count(),
            'orders_delivered' => (clone $baseQuery)->where('status', OrderStatus::DELIVERED)->count(),
        ];
    }

    /**
     * @param  array{from?: string, to?: string, boutique_id?: int, zone?: string}  $filters
     */
    private function scopedOrders(array $filters): Builder
    {
        return Order::query()
            ->where('status', '!=', OrderStatus::CANCELLED)
            ->when(isset($filters['from']), fn ($q) => $q->where('created_at', '>=', Carbon::parse($filters['from'])->startOfDay()))
            ->when(isset($filters['to']), fn ($q) => $q->where('created_at', '<=', Carbon::parse($filters['to'])->endOfDay()))
            ->when(isset($filters['zone']), fn ($q) => $q->where('delivery_zone', $filters['zone']))
            ->when(
                isset($filters['boutique_id']),
                fn ($q) => $q->whereHas('items', fn ($qq) => $qq->where('boutique_id', $filters['boutique_id']))
            );
    }

    /**
     * @param  array{from?: string, to?: string, boutique_id?: int, zone?: string}  $filters
     */
    private function marginRealized(array $filters): float
    {
        $sum = $this->scopedOrderItems($filters)
            ->selectRaw('COALESCE(SUM(order_items.margin_amount_mru_snapshot * order_items.quantity), 0) as total')
            ->value('total');

        return round((float) $sum, 2);
    }

    /**
     * @param  array{from?: string, to?: string, boutique_id?: int, zone?: string}  $filters
     * @return Collection<int, array{boutique_id: int|null, revenue_mru: float, order_count: int}>
     */
    private function salesByBoutique(array $filters): Collection
    {
        return $this->scopedOrderItems($filters)
            ->selectRaw('order_items.boutique_id, SUM(order_items.unit_price_mru_snapshot * order_items.quantity) as revenue_mru, COUNT(DISTINCT order_items.order_id) as order_count')
            ->groupBy('order_items.boutique_id')
            ->with('boutique:id,name')
            ->get()
            ->map(fn ($row) => [
                'boutique_id' => $row->boutique_id,
                'boutique_name' => $row->boutique?->name,
                'revenue_mru' => round((float) $row->revenue_mru, 2),
                'order_count' => (int) $row->order_count,
            ])
            ->sortByDesc('revenue_mru')
            ->values();
    }

    /**
     * @param  array{from?: string, to?: string, boutique_id?: int, zone?: string}  $filters
     * @return Collection<int, array{zone: string|null, revenue_mru: float, order_count: int}>
     */
    private function salesByZone(array $filters): Collection
    {
        return $this->scopedOrders($filters)
            ->selectRaw('delivery_zone, SUM(total_mru) as revenue_mru, COUNT(*) as order_count')
            ->groupBy('delivery_zone')
            ->get()
            ->map(fn ($row) => [
                'zone' => $row->delivery_zone,
                'revenue_mru' => round((float) $row->revenue_mru, 2),
                'order_count' => (int) $row->order_count,
            ])
            ->sortByDesc('revenue_mru')
            ->values();
    }

    /**
     * @param  array{from?: string, to?: string, boutique_id?: int, zone?: string}  $filters
     * @return Collection<int, array{product_variant_id: int|null, product_name: mixed, quantity_sold: int, revenue_mru: float}>
     */
    private function topProducts(array $filters): Collection
    {
        // product_name_snapshot est un jsonb : agréger dessus (MIN/MAX) n'est
        // pas portable entre moteurs, donc le nom est résolu séparément
        // (requête bornée à TOP_PRODUCTS_LIMIT lignes) plutôt qu'en SQL brut.
        $aggregates = $this->scopedOrderItems($filters)
            ->selectRaw('order_items.product_variant_id, SUM(order_items.quantity) as quantity_sold, SUM(order_items.unit_price_mru_snapshot * order_items.quantity) as revenue_mru')
            ->groupBy('order_items.product_variant_id')
            ->orderByDesc('quantity_sold')
            ->limit(self::TOP_PRODUCTS_LIMIT)
            ->get();

        $names = OrderItem::query()
            ->whereIn('product_variant_id', $aggregates->pluck('product_variant_id'))
            ->get(['product_variant_id', 'product_name_snapshot'])
            ->unique('product_variant_id')
            ->pluck('product_name_snapshot', 'product_variant_id');

        return $aggregates
            ->map(fn ($row) => [
                'product_variant_id' => $row->product_variant_id,
                'product_name' => $names->get($row->product_variant_id),
                'quantity_sold' => (int) $row->quantity_sold,
                'revenue_mru' => round((float) $row->revenue_mru, 2),
            ])
            ->values();
    }

    /**
     * @param  array{from?: string, to?: string, boutique_id?: int, zone?: string}  $filters
     */
    private function scopedOrderItems(array $filters): Builder
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', '!=', OrderStatus::CANCELLED)
            ->when(isset($filters['from']), fn ($q) => $q->where('orders.created_at', '>=', Carbon::parse($filters['from'])->startOfDay()))
            ->when(isset($filters['to']), fn ($q) => $q->where('orders.created_at', '<=', Carbon::parse($filters['to'])->endOfDay()))
            ->when(isset($filters['zone']), fn ($q) => $q->where('orders.delivery_zone', $filters['zone']))
            ->when(isset($filters['boutique_id']), fn ($q) => $q->where('order_items.boutique_id', $filters['boutique_id']));
    }
}
