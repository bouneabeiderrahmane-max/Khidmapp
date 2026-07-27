<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Reporting\DashboardReportService;
use App\Support\Permissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tableau de bord & reporting (CDC 8.9.7 + section 12). Voir le docblock
 * de DashboardReportService pour le périmètre exact des métriques
 * (aucun coût logistique suivi, donc pas de "commission"/marge nette).
 *
 * Deux niveaux d'accès déjà prévus par le RBAC (dashboard.view_full pour
 * l'administrateur, dashboard.view_limited pour le service client) :
 * les chiffres financiers (CA, marge, chiffre d'affaires par
 * boutique/zone/produit) sont réservés au niveau complet, cohérent avec
 * le reste de l'application où les données financières restent
 * admin-only (7.4).
 */
class DashboardController extends Controller
{
    public function __construct(private readonly DashboardReportService $reports) {}

    public function report(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->can(Permissions::DASHBOARD_VIEW_FULL) || $user->can(Permissions::DASHBOARD_VIEW_LIMITED), 403);

        $report = $this->reports->generate($this->filters($request));

        if (! $user->can(Permissions::DASHBOARD_VIEW_FULL)) {
            $report = $this->stripFinancials($report);
        }

        return response()->json(['data' => $report]);
    }

    /**
     * Réservé au niveau complet : un export CSV est par nature un document
     * financier destiné à quitter l'application.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $report = $this->reports->generate($this->filters($request));

        return response()->streamDownload(function () use ($report) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [__('dashboard.csv.indicator'), __('dashboard.csv.value')]);
            fputcsv($handle, [__('dashboard.csv.ca_mru'), $report['ca_mru']]);
            fputcsv($handle, [__('dashboard.csv.order_count'), $report['order_count']]);
            fputcsv($handle, [__('dashboard.csv.average_basket_mru'), $report['average_basket_mru']]);
            fputcsv($handle, [__('dashboard.csv.margin_realized_mru'), $report['margin_realized_mru']]);
            fputcsv($handle, [__('dashboard.csv.orders_in_progress'), $report['orders_in_progress']]);
            fputcsv($handle, [__('dashboard.csv.orders_delivered'), $report['orders_delivered']]);
            fputcsv($handle, []);

            fputcsv($handle, [__('dashboard.csv.sales_by_boutique')]);
            fputcsv($handle, [__('dashboard.csv.boutique'), __('dashboard.csv.revenue_mru'), __('dashboard.csv.order_count')]);
            foreach ($report['sales_by_boutique'] as $row) {
                fputcsv($handle, [$row['boutique_name'] ?? $row['boutique_id'], $row['revenue_mru'], $row['order_count']]);
            }
            fputcsv($handle, []);

            fputcsv($handle, [__('dashboard.csv.sales_by_zone')]);
            fputcsv($handle, [__('dashboard.csv.zone'), __('dashboard.csv.revenue_mru'), __('dashboard.csv.order_count')]);
            foreach ($report['sales_by_zone'] as $row) {
                fputcsv($handle, [$row['zone'], $row['revenue_mru'], $row['order_count']]);
            }
            fputcsv($handle, []);

            fputcsv($handle, [__('dashboard.csv.top_products')]);
            fputcsv($handle, [__('dashboard.csv.product'), __('dashboard.csv.quantity_sold'), __('dashboard.csv.revenue_mru')]);
            foreach ($report['top_products'] as $row) {
                $name = is_array($row['product_name']) ? ($row['product_name'][app()->getLocale()] ?? reset($row['product_name'])) : $row['product_name'];
                fputcsv($handle, [$name, $row['quantity_sold'], $row['revenue_mru']]);
            }

            fclose($handle);
        }, __('dashboard.csv.filename').'-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array{from?: string, to?: string, boutique_id?: int, zone?: string}
     */
    private function filters(Request $request): array
    {
        return array_filter([
            'from' => $request->string('from')->toString() ?: null,
            'to' => $request->string('to')->toString() ?: null,
            'boutique_id' => $request->filled('boutique_id') ? $request->integer('boutique_id') : null,
            'zone' => $request->string('zone')->toString() ?: null,
        ], fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>
     */
    private function stripFinancials(array $report): array
    {
        unset($report['ca_mru'], $report['average_basket_mru'], $report['margin_realized_mru']);

        $report['sales_by_boutique'] = collect($report['sales_by_boutique'])
            ->map(fn ($row) => collect($row)->except('revenue_mru')->all())
            ->all();

        $report['sales_by_zone'] = collect($report['sales_by_zone'])
            ->map(fn ($row) => collect($row)->except('revenue_mru')->all())
            ->all();

        $report['top_products'] = collect($report['top_products'])
            ->map(fn ($row) => collect($row)->except('revenue_mru')->all())
            ->all();

        return $report;
    }
}
