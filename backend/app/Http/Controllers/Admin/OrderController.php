<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminOrder\StoreManualOrderRequest;
use App\Http\Requests\AdminOrder\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Address;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\Order\OrderStatusTransitioner;
use App\Services\Pricing\CartPricingCalculator;
use App\Support\OrderActorType;
use App\Support\OrderStatus;
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

    /**
     * Supervision globale (7.4) : toutes les commandes, filtrables par
     * statut, boutique ou client.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = Order::query()
            ->with('items')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->when(
                $request->filled('boutique_id'),
                fn ($q) => $q->whereHas('items', fn ($qq) => $qq->where('boutique_id', $request->integer('boutique_id')))
            )
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return OrderResource::collection($orders);
    }

    public function show(Order $order): OrderResource
    {
        return new OrderResource($order->load(['items', 'statusHistories', 'payments', 'user']));
    }

    /**
     * Intervention manuelle sur le statut (7.4). L'acteur est déduit du
     * rôle de l'agent connecté pour la traçabilité (8.4.1).
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): OrderResource
    {
        $order = $this->transitioner->transition(
            $order,
            $request->string('status')->toString(),
            OrderActorType::forAgent($request->user()),
            $request->user()->id,
            $request->input('note'),
        );

        return new OrderResource($order->load(['items', 'statusHistories', 'payments']));
    }

    /**
     * Commande manuelle pour le compte d'un client (8.4.2 : assistance
     * téléphonique, vente assistée). Historise elle aussi taux/marge/frais,
     * et trace explicitement l'agent à l'origine de la commande.
     */
    public function store(StoreManualOrderRequest $request): JsonResponse
    {
        $address = Address::query()->findOrFail($request->integer('address_id'));

        $items = collect($request->input('items'))->map(function (array $line) {
            $item = new CartItem(['quantity' => $line['quantity']]);
            $item->setRelation('variant', ProductVariant::query()->with('product')->findOrFail($line['product_variant_id']));

            return $item;
        });

        $totals = $this->calculator->calculate($items);

        $order = DB::transaction(function () use ($request, $address, $totals) {
            $order = Order::query()->create([
                'user_id' => $request->integer('user_id'),
                'created_by_agent_id' => $request->user()->id,
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
                $variant = $line['item']->variant;
                $breakdown = $line['breakdown'];

                $order->items()->create([
                    'product_variant_id' => $variant->id,
                    'boutique_id' => $variant->product->boutique_id,
                    'product_name_snapshot' => $variant->product->name,
                    'size' => $variant->size,
                    'color' => $variant->color,
                    'quantity' => $line['item']->quantity,
                    'unit_price_eur' => $breakdown->basePriceEur,
                    'unit_price_mru_snapshot' => $breakdown->subtotalMru,
                    'margin_percent_snapshot' => $breakdown->marginPercent,
                    'margin_source_snapshot' => $breakdown->marginSource,
                ]);
            }

            $order->statusHistories()->create([
                'from_status' => null,
                'to_status' => OrderStatus::AWAITING_PAYMENT,
                'actor_type' => OrderActorType::forAgent($request->user()),
                'actor_id' => $request->user()->id,
                'note' => 'Commande manuelle créée pour le compte du client.',
            ]);

            return $order;
        });

        return (new OrderResource($order->load(['items', 'statusHistories', 'payments'])))->response()->setStatusCode(201);
    }
}
