<?php

namespace App\Services\Logistics;

use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Détection des dépassements de délai (CDC 8.6.2) : compare le temps
 * passé par chaque commande active dans son statut courant au délai
 * indicatif configuré (config/logistics.php). Calculé à la demande —
 * aucune table dédiée, aucune notification poussée (le module
 * Notifications, Sprint 9, n'existe pas encore) : ce service alimente
 * uniquement l'endpoint de supervision admin.
 */
class LogisticsAlertService
{
    /**
     * @return Collection<int, array{order: Order, status: string, entered_at: Carbon, hours_in_status: float, sla_hours: int, overrun_hours: float}>
     */
    public function currentAlerts(): Collection
    {
        $slaHours = config('logistics.step_sla_hours');

        return Order::query()
            ->active()
            ->with('latestStatusHistory')
            ->get()
            ->map(function (Order $order) use ($slaHours) {
                $sla = $slaHours[$order->status] ?? null;

                if ($sla === null) {
                    return null;
                }

                $enteredAt = $order->latestStatusHistory?->created_at ?? $order->created_at;
                $hoursInStatus = $enteredAt->diffInHours(now());
                $overrun = $hoursInStatus - $sla;

                if ($overrun <= 0) {
                    return null;
                }

                return [
                    'order' => $order,
                    'status' => $order->status,
                    'entered_at' => $enteredAt,
                    'hours_in_status' => $hoursInStatus,
                    'sla_hours' => $sla,
                    'overrun_hours' => $overrun,
                ];
            })
            ->filter()
            ->sortByDesc('overrun_hours')
            ->values();
    }
}
