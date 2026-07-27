<?php

namespace App\Http\Requests\Order;

use App\Support\PaymentMethod;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
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
