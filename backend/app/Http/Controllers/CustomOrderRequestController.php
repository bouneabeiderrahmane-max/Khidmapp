<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomOrder\StoreCustomOrderRequestRequest;
use App\Http\Resources\CustomOrderRequestResource;
use App\Models\CustomOrderRequest;
use App\Support\CustomOrderRequestStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * "Pedido personalizado" côté client : soumission d'une demande (un ou
 * plusieurs articles hors catalogue) et suivi de son statut. La création
 * ici ne crée jamais de Commande — seule l'approbation admin le fait (voir
 * CustomOrderRequestService::approve()).
 */
class CustomOrderRequestController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $requests = $request->user()->customOrderRequests()
            ->with(['items.boutique', 'order'])
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return CustomOrderRequestResource::collection($requests);
    }

    public function show(Request $request, CustomOrderRequest $customOrderRequest): CustomOrderRequestResource
    {
        $this->authorizeOwnership($request, $customOrderRequest);

        return new CustomOrderRequestResource($customOrderRequest->load(['items.boutique', 'address', 'order']));
    }

    public function store(StoreCustomOrderRequestRequest $request): JsonResponse
    {
        $customOrderRequest = DB::transaction(function () use ($request) {
            $extraWeightKg = (float) $request->input('extra_weight_kg', 0);

            $customOrderRequest = CustomOrderRequest::query()->create([
                'user_id' => $request->user()->id,
                'status' => CustomOrderRequestStatus::PENDING,
                'address_id' => $request->integer('address_id'),
                'payment_method' => $request->input('payment_method'),
                'weight_tier' => $request->input('weight_tier'),
                'extra_weight_kg' => $extraWeightKg > 0 ? $extraWeightKg : null,
            ]);

            foreach ($request->input('items') as $line) {
                $customOrderRequest->items()->create([
                    'boutique_id' => $line['boutique_id'] ?? null,
                    'product_url' => $line['product_url'],
                    'quantity' => $line['quantity'],
                    'estimated_price_eur' => $line['estimated_price_eur'],
                    'notes' => $line['notes'] ?? null,
                ]);
            }

            return $customOrderRequest;
        });

        return (new CustomOrderRequestResource($customOrderRequest->load(['items.boutique', 'address'])))
            ->response()
            ->setStatusCode(201);
    }

    private function authorizeOwnership(Request $request, CustomOrderRequest $customOrderRequest): void
    {
        abort_if($customOrderRequest->user_id !== $request->user()->id, 403);
    }
}
