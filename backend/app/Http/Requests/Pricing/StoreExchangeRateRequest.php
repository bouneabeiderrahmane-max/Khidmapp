<?php

namespace App\Http\Requests\Pricing;

use Illuminate\Foundation\Http\FormRequest;

class StoreExchangeRateRequest extends FormRequest
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
            'currency_pair' => ['sometimes', 'string', 'max:10'],
            'rate' => ['required', 'numeric', 'gt:0'],
            'effective_at' => ['sometimes', 'date'],
        ];
    }
}
