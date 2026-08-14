<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveCustomOrderRequestRequest;
use App\Http\Requests\Admin\RejectCustomOrderRequestRequest;
use App\Http\Resources\CustomOrderRequestResource;
use App\Models\CustomOrderRequest;
use App\Services\CustomOrder\CustomOrderRequestService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Revue manuelle des "pedidos personalizados" (7.4 : "intervention
 * manuelle" — extension du même principe de supervision aux demandes hors
 * catalogue, avant qu'elles ne deviennent des commandes).
 */
class CustomOrderRequestController extends Controller
{
    public function __construct(private readonly CustomOrderRequestService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $requests = CustomOrderRequest::query()
            ->with(['items.boutique', 'user'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return CustomOrderRequestResource::collection($requests);
    }

    public function show(CustomOrderRequest $customOrderRequest): CustomOrderRequestResource
    {
        return new CustomOrderRequestResource($customOrderRequest->load(['items.boutique', 'address', 'order', 'user', 'reviewer']));
    }

    public function approve(ApproveCustomOrderRequestRequest $request, CustomOrderRequest $customOrderRequest): CustomOrderRequestResource
    {
        $customOrderRequest = $this->service->approve($customOrderRequest, $request->user(), $request->input('note'));

        return new CustomOrderRequestResource($customOrderRequest->load(['items.boutique', 'order']));
    }

    public function reject(RejectCustomOrderRequestRequest $request, CustomOrderRequest $customOrderRequest): CustomOrderRequestResource
    {
        $customOrderRequest = $this->service->reject($customOrderRequest, $request->user(), $request->string('reason')->toString());

        return new CustomOrderRequestResource($customOrderRequest->load(['items.boutique']));
    }
}
