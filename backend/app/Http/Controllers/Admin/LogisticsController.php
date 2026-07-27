<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\Logistics\LogisticsAlertService;
use App\Support\OrderStatus;
use Illuminate\Http\JsonResponse;

/**
 * Tableau de bord logistique (CDC 8.6.2) : nombre de commandes actives par
 * étape en temps réel, et détection des dépassements de délai indicatif.
 */
class LogisticsController extends Controller
{
    public function __construct(private readonly LogisticsAlertService $alerts) {}

    public function dashboard(): JsonResponse
    {
        $counts = Order::query()->active()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');

        $steps = collect(OrderStatus::all())
            ->reject(fn (string $status) => in_array($status, [OrderStatus::DRAFT, OrderStatus::DELIVERED, OrderStatus::CANCELLED, OrderStatus::REFUNDED], true))
            ->map(fn (string $status) => [
                'status' => $status,
                'status_label' => __('khidmapp.order_status.'.$status),
                'count' => (int) ($counts[$status] ?? 0),
            ])
            ->values();

        return response()->json(['data' => $steps]);
    }

    public function alerts(): JsonResponse
    {
        $data = $this->alerts->currentAlerts()->map(fn (array $alert) => [
            'order' => new OrderResource($alert['order']),
            'status' => $alert['status'],
            'status_label' => __('khidmapp.order_status.'.$alert['status']),
            'entered_at' => $alert['entered_at'],
            'hours_in_status' => round($alert['hours_in_status'], 1),
            'sla_hours' => $alert['sla_hours'],
            'overrun_hours' => round($alert['overrun_hours'], 1),
        ])->values();

        return response()->json(['data' => $data]);
    }
}
