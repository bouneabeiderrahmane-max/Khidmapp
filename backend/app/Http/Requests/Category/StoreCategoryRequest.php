<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
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
            'name' => ['required', 'array'],
            'name.fr' => ['required', 'string', 'max:255'],
            'name.ar' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:categories,id'],
        ];
    }
}
