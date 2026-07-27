<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pricing\StoreExchangeRateRequest;
use App\Http\Resources\ExchangeRateResource;
use App\Models\ExchangeRate;
use App\Services\Pricing\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExchangeRateController extends Controller
{
    /**
     * Historique des taux (8.9.3 : "historique des taux appliqués").
     */
    public function index(): AnonymousResourceCollection
    {
        return ExchangeRateResource::collection(
            ExchangeRate::query()->orderByDesc('effective_at')->paginate(20)
        );
    }

    public function store(StoreExchangeRateRequest $request): JsonResponse
    {
        $currencyPair = $request->input('currency_pair', config('pricing.default_currency_pair'));

        $rate = ExchangeRate::query()->create([
            'currency_pair' => $currencyPair,
            'rate' => $request->input('rate'),
            'effective_at' => $request->input('effective_at', now()),
            'created_by' => $request->user()->id,
        ]);

        PricingService::forgetExchangeRateCache($currencyPair);

        return (new ExchangeRateResource($rate))->response()->setStatusCode(201);
    }
}
