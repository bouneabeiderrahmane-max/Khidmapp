<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pricing\StoreDeliveryFeeTierRequest;
use App\Http\Requests\Pricing\UpdateDeliveryFeeTierRequest;
use App\Http\Resources\DeliveryFeeTierResource;
use App\Models\DeliveryFeeTier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DeliveryFeeTierController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return DeliveryFeeTierResource::collection(
            DeliveryFeeTier::query()->orderBy('zone')->orderBy('min_price_mru')->get()
        );
    }

    public function store(StoreDeliveryFeeTierRequest $request): JsonResponse
    {
        $tier = DeliveryFeeTier::query()->create($request->validated());

        return (new DeliveryFeeTierResource($tier))->response()->setStatusCode(201);
    }

    public function update(UpdateDeliveryFeeTierRequest $request, DeliveryFeeTier $deliveryFeeTier): DeliveryFeeTierResource
    {
        $deliveryFeeTier->update($request->validated());

        return new DeliveryFeeTierResource($deliveryFeeTier->fresh());
    }

    public function destroy(DeliveryFeeTier $deliveryFeeTier): JsonResponse
    {
        $deliveryFeeTier->delete();

        return response()->json(['message' => 'ok']);
    }
}
