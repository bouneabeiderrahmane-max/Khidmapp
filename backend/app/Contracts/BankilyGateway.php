<?php

namespace App\Contracts;

use App\Models\Order;

interface BankilyGateway
{
    /**
     * Initiate a Bankily payment for the given order. Returns a reference
     * plus whatever client-facing instructions the real API would return
     * (e.g. a USSD code or deep link) — implementations are swapped via the
     * binding in AppServiceProvider so a real Bankily integration can
     * replace the placeholder without touching any caller.
     *
     * @return array{reference: string, instructions: string}
     */
    public function initiate(Order $order): array;
}
