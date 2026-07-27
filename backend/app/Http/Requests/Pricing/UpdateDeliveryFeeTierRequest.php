<?php

namespace App\Http\Requests\Pricing;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliveryFeeTierRequest extends FormRequest
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
            'zone' => ['sometimes', 'string', 'max:255'],
            'min_price_mru' => ['sometimes', 'numeric', 'min:0'],
            'max_price_mru' => ['sometimes', 'nullable', 'numeric', 'gt:min_price_mru'],
            'fee_mru' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
