<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Correction manuelle des champs traduits automatiquement (8.2.1) et
     * réaffectation de catégorie — pas de modification du prix ou des
     * variantes, qui viennent de la synchronisation.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'array'],
            'name.fr' => ['required_with:name', 'string', 'max:255'],
            'name.ar' => ['required_with:name', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'array'],
            'description.fr' => ['nullable', 'string'],
            'description.ar' => ['nullable', 'string'],
            'category_id' => ['sometimes', 'nullable', 'exists:categories,id'],
        ];
    }
}
