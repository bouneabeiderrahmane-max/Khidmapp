<?php

namespace App\Http\Requests\Cart;

use App\Models\ProductVariant;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

class StoreCartItemRequest extends FormRequest
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
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:99'],
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $variantId = $this->input('product_variant_id');

            if ($variantId === null) {
                return;
            }

            $variant = ProductVariant::query()->with('product.boutique')->find($variantId);
            $available = $variant
                && $variant->product?->status === 'active'
                && $variant->product?->boutique?->status === 'active';

            if (! $available) {
                $validator->errors()->add('product_variant_id', __('khidmapp.product_unavailable'));
            }
        });
    }
}
