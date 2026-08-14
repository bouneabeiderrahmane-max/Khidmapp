<?php

namespace App\Http\Requests\CustomOrder;

use App\Support\PaymentMethod;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomOrderRequestRequest extends FormRequest
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
            'address_id' => ['required', 'integer'],
            'payment_method' => ['required', Rule::in(PaymentMethod::all())],
            'items' => ['required', 'array', 'min:1'],
            'items.*.boutique_id' => ['nullable', 'integer', 'exists:boutiques,id'],
            'items.*.product_url' => ['required', 'url', 'max:2048'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.estimated_price_eur' => ['required', 'numeric', 'min:0.01'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $addressId = $this->input('address_id');

            if ($addressId === null) {
                return;
            }

            $belongsToUser = $this->user()->addresses()->whereKey($addressId)->exists();

            if (! $belongsToUser) {
                $validator->errors()->add('address_id', __('khidmapp.address_not_found'));
            }
        });
    }
}
