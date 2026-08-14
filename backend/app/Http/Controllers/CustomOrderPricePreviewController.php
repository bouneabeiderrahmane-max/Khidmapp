<?php

namespace App\Http\Controllers;

use App\Services\Pricing\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Aperçu public du prix final (MRU, marge incluse) pour un prix EUR saisi
 * librement — utilisé par le formulaire mobile de "pedido personalizado"
 * (CDC — extension, voir docs/PLAN.md §7duodecies ter) pour que le client
 * voie tout de suite l'équivalent en MRU du prix qu'il lit sur le site
 * externe d'une boutique, sans exposer d'URL ni de prix EUR ailleurs que
 * dans ce calcul (règle transverse : le client ne voit jamais de prix EUR
 * seul). Public (pas d'authentification) : ne renvoie rien de spécifique à
 * un client ou une commande, seulement un calcul.
 */
class CustomOrderPricePreviewController extends Controller
{
    public function __construct(private readonly PricingService $pricing) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'price_eur' => ['required', 'numeric', 'min:0.01'],
            'boutique_id' => ['nullable', 'integer', 'exists:boutiques,id'],
        ]);

        $breakdown = $this->pricing->priceForExternalItem(
            (float) $request->input('price_eur'),
            $request->integer('boutique_id') ?: null,
        );

        return response()->json([
            'data' => [
                'price_eur' => $breakdown->basePriceEur,
                'exchange_rate' => $breakdown->exchangeRate,
                'margin_percent' => $breakdown->marginPercent,
                'margin_source' => $breakdown->marginSource,
                'subtotal_mru' => $breakdown->subtotalMru,
            ],
        ]);
    }
}
