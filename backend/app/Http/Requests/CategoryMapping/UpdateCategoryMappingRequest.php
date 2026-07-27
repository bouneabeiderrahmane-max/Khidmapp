<?php

namespace App\Http\Requests\CategoryMapping;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryMappingRequest extends FormRequest
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
            'category_id' => ['sometimes', 'nullable', 'exists:categories,id'],
        ];
    }
}
