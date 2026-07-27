<?php

namespace App\Http\Requests\Boutique;

use App\Support\BoutiqueStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBoutiqueRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'logo_url' => ['nullable', 'url', 'max:2048'],
            'banner_url' => ['nullable', 'url', 'max:2048'],
            'base_url' => ['required', 'url', 'max:2048'],
            'country_code' => ['required', 'string', 'size:2'],
            'currency_code' => ['required', 'string', 'size:3'],
            // "sometimes": if omitted, the column keeps its DB default
            // (en_test) instead of an explicit NULL overriding it.
            'status' => ['sometimes', Rule::in(BoutiqueStatus::all())],
            'default_margin_percent' => ['nullable', 'numeric', 'between:0,1000'],
            'sync_config' => ['nullable', 'array'],
            'sync_config.frequency_hours' => ['nullable', 'integer', 'min:1'],
            'sync_config.included_categories' => ['nullable', 'array'],
            'sync_config.included_categories.*' => ['string'],
            'sync_config.excluded_categories' => ['nullable', 'array'],
            'sync_config.excluded_categories.*' => ['string'],
            'sync_config.translation_auto' => ['nullable', 'boolean'],
            'sync_config.alert_threshold_percent' => ['nullable', 'integer', 'between:0,100'],
        ];
    }
}
