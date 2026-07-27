<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminPayment\RejectPaymentRequest;
use App\Http\Requests\AdminPayment\RequestPaymentInfoRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\Payment\ManualPaymentReviewer;
use App\Support\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Revue des paiements manuels par le service client/administrateur (CDC
 * 7.3, 8.5.2) : consultation de la file d'attente, preuve associée,
 * validation/refus/demande de complément.
 */
class PaymentController extends Controller
{
    public function __construct(private readonly ManualPaymentReviewer $manualPayments) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $payments = Payment::query()
            ->where('method', PaymentMethod::MANUAL)
            ->with('order')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return PaymentResource::collection($payments);
    }

    public function proof(Payment $payment): StreamedResponse
    {
        abort_unless($payment->proof_file_path !== null, 404);

        return Storage::response($payment->proof_file_path);
    }

    public function validatePayment(Request $request, Payment $payment): PaymentResource
    {
        return new PaymentResource($this->manualPayments->validate($payment, $request->user()));
    }

    public function reject(RejectPaymentRequest $request, Payment $payment): PaymentResource
    {
        return new PaymentResource($this->manualPayments->reject($payment, $request->user(), $request->string('reason')->toString()));
    }

    public function requestInfo(RequestPaymentInfoRequest $request, Payment $payment): PaymentResource
    {
        return new PaymentResource($this->manualPayments->requestMoreInfo($payment, $request->user(), $request->string('note')->toString()));
    }
}
