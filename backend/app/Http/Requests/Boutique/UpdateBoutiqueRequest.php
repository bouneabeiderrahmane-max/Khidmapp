<?php

namespace App\Http\Requests\Boutique;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBoutiqueRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'logo_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'banner_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'base_url' => ['sometimes', 'url', 'max:2048'],
            'country_code' => ['sometimes', 'string', 'size:2'],
            'currency_code' => ['sometimes', 'string', 'size:3'],
            'default_margin_percent' => ['sometimes', 'nullable', 'numeric', 'between:0,1000'],
            'sync_config' => ['sometimes', 'nullable', 'array'],
            'sync_config.frequency_hours' => ['nullable', 'integer', 'min:1'],
            'sync_config.included_categories' => ['nullable', 'array'],
            'sync_config.included_categories.*' => ['string'],
            'sync_config.excluded_categories' => ['nullable', 'array'],
            'sync_config.excluded_categories.*' => ['string'],
            'sync_config.translation_auto' => ['nullable', 'boolean'],
            'sync_config.alert_threshold_percent' => ['nullable', 'integer', 'between:0,100'],
            'sync_config.unavailable_grace_days' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
