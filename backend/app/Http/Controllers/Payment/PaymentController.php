<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\SubmitPaymentProofRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Services\Payment\BankilyPaymentService;
use App\Services\Payment\ManualPaymentReviewer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private readonly BankilyPaymentService $bankily,
        private readonly ManualPaymentReviewer $manualPayments,
    ) {}

    /**
     * Initie un paiement Bankily pour la commande (7.2.3, 8.5.1). La
     * confirmation arrive de façon asynchrone via le webhook Bankily.
     */
    public function initiateBankily(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOwnership($request, $order);

        $payment = $this->bankily->initiate($order, $request->user());

        return (new PaymentResource($payment))->response()->setStatusCode(201);
    }

    /**
     * Soumet une preuve de paiement manuel (capture d'écran, photo de reçu
     * — 7.2.3, 8.5.2).
     */
    public function submitProof(SubmitPaymentProofRequest $request, Order $order): JsonResponse
    {
        $this->authorizeOwnership($request, $order);

        $payment = $this->manualPayments->submitProof($order, $request->user(), $request->file('proof'));

        return (new PaymentResource($payment))->response()->setStatusCode(201);
    }

    private function authorizeOwnership(Request $request, Order $order): void
    {
        abort_if($order->user_id !== $request->user()->id, 403);
    }
}
