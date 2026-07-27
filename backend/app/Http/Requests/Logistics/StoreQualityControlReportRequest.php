<?php

namespace App\Http\Requests\Logistics;

use Illuminate\Foundation\Http\FormRequest;

class StoreQualityControlReportRequest extends FormRequest
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
            'is_conforme' => ['required', 'boolean'],
            // Motif obligatoire en cas de non-conformité (8.6.1 : "signalement
            // immédiat d'une non-conformité").
            'notes' => ['required_if:is_conforme,false', 'nullable', 'string', 'max:2000'],
        ];
    }
}
