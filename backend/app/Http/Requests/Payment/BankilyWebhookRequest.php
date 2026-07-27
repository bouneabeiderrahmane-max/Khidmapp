<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Payload attendu d'une notification Bankily — forme supposée en
 * l'absence de documentation officielle de l'API réelle (voir
 * StubBankilyGateway). La vérification de signature (en-tête
 * X-Bankily-Signature) est faite en amont dans BankilyWebhookController,
 * pas ici.
 */
class BankilyWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reference' => ['required', 'string'],
            'status' => ['required', Rule::in(['succeeded', 'failed', 'expired', 'cancelled'])],
            'failure_reason' => ['nullable', 'string'],
        ];
    }
}
