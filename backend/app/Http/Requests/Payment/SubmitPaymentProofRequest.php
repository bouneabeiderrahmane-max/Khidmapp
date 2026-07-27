<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class SubmitPaymentProofRequest extends FormRequest
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
            'proof' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('payments.proof_max_size_kb')],
        ];
    }
}
