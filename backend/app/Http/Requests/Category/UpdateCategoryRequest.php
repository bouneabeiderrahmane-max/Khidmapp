<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
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
            'name' => ['sometimes', 'array'],
            'name.fr' => ['required_with:name', 'string', 'max:255'],
            'name.ar' => ['required_with:name', 'string', 'max:255'],
            'parent_id' => [
                'sometimes', 'nullable',
                Rule::exists('categories', 'id')->whereNot('id', $this->route('category')?->id),
            ],
        ];
    }
}
