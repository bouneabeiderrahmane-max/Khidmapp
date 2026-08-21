<?php

namespace App\Http\Controllers\Cart;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\StoreCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Services\Pricing\CartPricingCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly CartPricingCalculator $calculator) {}

    public function show(Request $request): JsonResponse
    {
        return $this->respondWithCart($this->currentCart($request));
    }

    public function storeItem(StoreCartItemRequest $request): JsonResponse
    {
        $cart = $this->currentCart($request);

        $item = $cart->items()->where('product_variant_id', $request->integer('product_variant_id'))->first();

        if ($item) {
            $item->increment('quantity', $request->integer('quantity', 1));
        } else {
            $cart->items()->create([
                'product_variant_id' => $request->integer('product_variant_id'),
                'quantity' => $request->integer('quantity', 1),
            ]);
        }

        return $this->respondWithCart($cart->fresh());
    }

    public function updateItem(UpdateCartItemRequest $request, CartItem $cartItem): JsonResponse
    {
        $this->authorizeOwnership($request, $cartItem);

        $cartItem->update(['quantity' => $request->integer('quantity')]);

        return $this->respondWithCart($cartItem->cart->fresh());
    }

    public function destroyItem(Request $request, CartItem $cartItem): JsonResponse
    {
        $this->authorizeOwnership($request, $cartItem);
        $cart = $cartItem->cart;
        $cartItem->delete();

        return $this->respondWithCart($cart->fresh());
    }

    private function currentCart(Request $request): Cart
    {
        return Cart::query()->firstOrCreate(['user_id' => $request->user()->id]);
    }

    private function authorizeOwnership(Request $request, CartItem $cartItem): void
    {
        abort_if($cartItem->cart->user_id !== $request->user()->id, 403);
    }

    private function respondWithCart(Cart $cart): JsonResponse
    {
        $items = $cart->items()->with('variant.product.boutique')->get();
        $totals = $this->calculator->calculate($items);

        return response()->json([
            'data' => [
                'items' => collect($totals->lines)->map(fn (array $line) => [
                    'id' => $line['item']->id,
                    'variant_id' => $line['item']->product_variant_id,
                    'product' => [
                        'id' => $line['item']->variant->product->id,
                        'name' => $line['item']->variant->product->name,
                        'image' => $line['item']->variant->product->images[0] ?? null,
                        'boutique' => $line['item']->variant->product->boutique->name,
                    ],
                    'size' => $line['item']->variant->size,
                    'color' => $line['item']->variant->color,
                    'quantity' => $line['item']->quantity,
                    'unit_price_mru' => $line['breakdown']->subtotalMru,
                    'line_subtotal_mru' => $line['line_subtotal_mru'],
                ])->values(),
                'subtotal_mru' => $totals->subtotalMru,
                'delivery_zone' => $totals->deliveryZone,
                'delivery_fee_mru' => $totals->deliveryFeeMru,
                'management_fee_mru' => $totals->managementFeeMru,
                'total_mru' => $totals->totalMru,
            ],
        ]);
    }
}
