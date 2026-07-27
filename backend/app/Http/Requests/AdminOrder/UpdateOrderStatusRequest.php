<?php

namespace App\Http\Requests\AdminOrder;

use App\Support\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
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
            'status' => ['required', Rule::in(OrderStatus::all())],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
