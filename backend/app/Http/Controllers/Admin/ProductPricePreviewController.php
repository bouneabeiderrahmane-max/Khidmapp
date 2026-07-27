<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Pricing\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductPricePreviewController extends Controller
{
    public function __construct(private readonly PricingService $pricing) {}

    /**
     * Aperçu du prix final d'une variante, utile pour vérifier le calcul
     * avant que le catalogue public (Sprint 5) ou la commande (Sprint 6)
     * n'existent.
     */
    public function __invoke(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'variant_id' => ['required', 'integer'],
            'zone' => ['sometimes', 'string'],
        ]);

        $variant = $product->variants()->findOrFail($request->integer('variant_id'));

        $breakdown = $this->pricing->priceForVariant($variant, $request->string('zone')->toString() ?: null);

        return response()->json(['data' => $breakdown->toArray()]);
    }
}
