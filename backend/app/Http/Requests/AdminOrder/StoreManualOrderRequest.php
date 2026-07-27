<?php

namespace App\Http\Requests\AdminOrder;

use App\Models\Address;
use App\Support\PaymentMethod;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Commande manuelle créée par le service client/administrateur pour le
 * compte d'un client (8.4.2 : assistance téléphonique, vente assistée).
 */
class StoreManualOrderRequest extends FormRequest
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
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'address_id' => ['required', 'integer', 'exists:addresses,id'],
            'payment_method' => ['required', Rule::in(PaymentMethod::all())],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $userId = $this->input('user_id');
            $addressId = $this->input('address_id');

            if ($userId === null || $addressId === null) {
                return;
            }

            $belongsToUser = Address::query()->whereKey($addressId)->where('user_id', $userId)->exists();

            if (! $belongsToUser) {
                $validator->errors()->add('address_id', __('khidmapp.address_not_found'));
            }
        });
    }
}
