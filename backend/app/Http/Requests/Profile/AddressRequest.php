<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
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
        $sometimesOnUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH') ? 'sometimes' : 'required';

        return [
            'label' => [$sometimesOnUpdate, 'string', 'max:255'],
            'city' => [$sometimesOnUpdate, 'string', 'max:255'],
            'area' => ['nullable', 'string', 'max:255'],
            'geo_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'geo_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'phone' => ['nullable', 'string', 'regex:/^\+\d{8,15}$/'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
