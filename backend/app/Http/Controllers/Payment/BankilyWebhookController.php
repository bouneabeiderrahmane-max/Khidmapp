<?php

namespace App\Http\Controllers\Payment;

use App\Exceptions\Payment\InvalidWebhookSignatureException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\BankilyWebhookRequest;
use App\Services\Payment\BankilyPaymentService;
use Illuminate\Http\JsonResponse;

/**
 * Point d'entrée public appelé par Bankily pour notifier le résultat d'un
 * paiement (8.5.1). Aucune spécification officielle du schéma de
 * signature Bankily n'étant disponible, la protection ici est un simple
 * partage de secret (en-tête X-Bankily-Signature) — à remplacer par le
 * vrai mécanisme de signature Bankily dès qu'il sera connu.
 */
class BankilyWebhookController extends Controller
{
    public function __construct(private readonly BankilyPaymentService $bankily) {}

    public function handle(BankilyWebhookRequest $request): JsonResponse
    {
        $signature = (string) $request->header('X-Bankily-Signature');
        $expected = (string) config('payments.bankily.webhook_secret');

        if ($expected === '' || ! hash_equals($expected, $signature)) {
            throw InvalidWebhookSignatureException::make();
        }

        $this->bankily->handleWebhook($request->validated());

        return response()->json(['message' => 'ok']);
    }
}
