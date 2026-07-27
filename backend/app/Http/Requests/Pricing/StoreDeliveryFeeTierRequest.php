<?php

namespace App\Http\Requests\Pricing;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryFeeTierRequest extends FormRequest
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
            'zone' => ['required', 'string', 'max:255'],
            'min_price_mru' => ['required', 'numeric', 'min:0'],
            'max_price_mru' => ['nullable', 'numeric', 'gt:min_price_mru'],
            'fee_mru' => ['required', 'numeric', 'min:0'],
        ];
    }
}
