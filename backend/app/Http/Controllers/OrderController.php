<?php

namespace App\Http\Controllers;

use App\Http\Requests\Order\CancelOrderRequest;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Order\OrderStatusTransitioner;
use App\Services\Pricing\CartPricingCalculator;
use App\Support\OrderActorType;
use App\Support\OrderStatus;
use App\Support\PaymentMethod;
use App\Support\PaymentStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct(
        private readonly CartPricingCalculator $calculator,
        private readonly OrderStatusTransitioner $transitioner,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = $request->user()->orders()
            ->with('items')
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return OrderResource::collection($orders);
    }

    public function show(Request $request, Order $order): OrderResource
    {
        $this->authorizeOwnership($request, $order);

        return new OrderResource($order->load(['items', 'statusHistories', 'payments']));
    }

    /**
     * Passage de commande depuis le panier (7.2.2 : sélection de l'adresse
     * et du mode de paiement). Historise taux/marge/frais (8.3.2).
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $cart = Cart::query()->where('user_id', $request->user()->id)->with('items.variant.product.boutique')->first();
        $items = $cart?->items ?? collect();

        abort_if($items->isEmpty(), 422, __('khidmapp.cart_empty'));

        if (config('payments.block_new_orders_with_pending_manual_proof')) {
            $hasPendingProof = Payment::query()
                ->where('method', PaymentMethod::MANUAL)
                ->where('status', PaymentStatus::PENDING)
                ->whereHas('order', fn ($q) => $q->where('user_id', $request->user()->id))
                ->exists();

            abort_if($hasPendingProof, 422, __('khidmapp.pending_payment_proof_blocks_checkout'));
        }

        $address = Address::query()->findOrFail($request->integer('address_id'));
        $totals = $this->calculator->calculate($items);

        $order = DB::transaction(function () use ($request, $address, $totals, $items) {
            $order = Order::query()->create([
                'user_id' => $request->user()->id,
                'status' => OrderStatus::AWAITING_PAYMENT,
                'address_id' => $address->id,
                'shipping_label' => $address->label,
                'shipping_city' => $address->city,
                'shipping_area' => $address->area,
                'shipping_phone' => $address->phone,
                'payment_method' => $request->input('payment_method'),
                'subtotal_eur' => $totals->subtotalEur,
                'exchange_rate_snapshot' => $totals->exchangeRate,
                'subtotal_mru' => $totals->subtotalMru,
                'delivery_fee_snapshot_mru' => $totals->deliveryFeeMru,
                'total_mru' => $totals->totalMru,
            ]);

            foreach ($totals->lines as $line) {
                $cartItem = $line['item'];
                $breakdown = $line['breakdown'];
                $product = $cartItem->variant->product;

                $order->items()->create([
                    'product_variant_id' => $cartItem->variant->id,
                    'boutique_id' => $product->boutique_id,
                    'product_name_snapshot' => $product->name,
                    'size' => $cartItem->variant->size,
                    'color' => $cartItem->variant->color,
                    'quantity' => $cartItem->quantity,
                    'unit_price_eur' => $breakdown->basePriceEur,
                    'unit_price_mru_snapshot' => $breakdown->subtotalMru,
                    'margin_percent_snapshot' => $breakdown->marginPercent,
                    'margin_source_snapshot' => $breakdown->marginSource,
                ]);
            }

            $order->statusHistories()->create([
                'from_status' => null,
                'to_status' => OrderStatus::AWAITING_PAYMENT,
                'actor_type' => OrderActorType::CLIENT,
                'actor_id' => $request->user()->id,
            ]);

            $items->each(fn ($item) => $item->delete());

            return $order;
        });

        return (new OrderResource($order->load(['items', 'statusHistories', 'payments'])))->response()->setStatusCode(201);
    }

    /**
     * Auto-annulation par le client : uniquement dans la fenêtre gratuite
     * (8.4.1). Au-delà, le client doit passer par le service client.
     */
    public function cancel(CancelOrderRequest $request, Order $order): OrderResource
    {
        $this->authorizeOwnership($request, $order);

        abort_unless(OrderStatus::isFreeCancellation($order->status), 422, __('khidmapp.order_cancellation_blocked'));

        $order = $this->transitioner->transition(
            $order,
            OrderStatus::CANCELLED,
            OrderActorType::CLIENT,
            $request->user()->id,
            $request->input('reason'),
        );

        return new OrderResource($order->load(['items', 'statusHistories', 'payments']));
    }

    private function authorizeOwnership(Request $request, Order $order): void
    {
        abort_if($order->user_id !== $request->user()->id, 403);
    }
}
