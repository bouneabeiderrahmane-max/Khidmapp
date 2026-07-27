<?php

namespace App\Http\Requests\AdminPayment;

use Illuminate\Foundation\Http\FormRequest;

class RequestPaymentInfoRequest extends FormRequest
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
            'note' => ['required', 'string', 'max:1000'],
        ];
    }
}
