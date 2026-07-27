<?php

namespace App\Services\Payment;

use App\Contracts\BankilyGateway;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Placeholder gateway: generates a local reference and a fixed set of
 * instructions instead of calling the real Bankily API. The cahier des
 * charges confirms Bankily as the automatic payment method (8.5.1) but
 * gives no API credentials, request/response format, or webhook signature
 * scheme — this stands in until Khidmapp has real Bankily merchant access.
 * Do not treat this as a production-ready payment integration.
 */
class StubBankilyGateway implements BankilyGateway
{
    public function initiate(Order $order): array
    {
        $reference = 'BKY-'.Str::upper(Str::random(12));

        Log::info('[Bankily placeholder] payment initiated', [
            'order_id' => $order->id,
            'reference' => $reference,
            'amount_mru' => $order->total_mru,
        ]);

        return [
            'reference' => $reference,
            'instructions' => __('khidmapp.bankily_payment_instructions'),
        ];
    }
}
