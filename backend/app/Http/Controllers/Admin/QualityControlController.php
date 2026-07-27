<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Logistics\StoreQualityControlReportRequest;
use App\Http\Resources\QualityControlReportResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\QualityControlReport;
use App\Services\Complaint\ComplaintService;
use App\Services\Notification\NotificationService;
use App\Support\ComplaintCategory;
use App\Support\NotificationTemplate;
use App\Support\OrderStatus;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Contrôle qualité à réception (CDC 8.6.1, étape 6/10) : vérification que
 * l'article reçu correspond à la référence commandée et de son état
 * visuel. Effectué "physiquement" par l'entrepôt 3PL, enregistré ici par
 * un agent Khidmapp (service client/administrateur) faute de rôle 3PL
 * distinct dans le RBAC confirmé (7.1, 3 rôles seulement).
 *
 * Une non-conformité ouvre automatiquement une réclamation interne (8.6.1)
 * et notifie le client — voir ComplaintService::openAutomatically().
 */
class QualityControlController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly ComplaintService $complaints,
    ) {}

    public function index(Order $order): AnonymousResourceCollection
    {
        $reports = QualityControlReport::query()
            ->whereHas('orderItem', fn ($q) => $q->where('order_id', $order->id))
            ->with('reporter')
            ->latest()
            ->get();

        return QualityControlReportResource::collection($reports);
    }

    public function store(StoreQualityControlReportRequest $request, Order $order, OrderItem $orderItem): QualityControlReportResource
    {
        abort_if($orderItem->order_id !== $order->id, 404);
        abort_unless($order->status === OrderStatus::QUALITY_CONTROL, 422, __('khidmapp.order_not_in_quality_control'));

        $report = $orderItem->qualityControlReports()->create([
            'reported_by' => $request->user()->id,
            'is_conforme' => $request->boolean('is_conforme'),
            'notes' => $request->input('notes'),
        ]);

        if (! $report->is_conforme) {
            $this->complaints->openAutomatically(
                $order,
                ComplaintCategory::PRODUIT_NON_CONFORME,
                __('khidmapp.quality_control_anomaly_complaint_message', ['item_id' => $orderItem->id, 'notes' => (string) $report->notes]),
            );

            $this->notifications->notify($order->user, NotificationTemplate::QUALITY_CONTROL_ANOMALY, ['order_id' => $order->id]);
        }

        return new QualityControlReportResource($report->load('reporter'));
    }
}
