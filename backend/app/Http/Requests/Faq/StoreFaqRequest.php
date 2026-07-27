<?php

namespace App\Http\Requests\Faq;

use Illuminate\Foundation\Http\FormRequest;

class StoreFaqRequest extends FormRequest
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
            'question.fr' => ['required', 'string', 'max:500'],
            'question.ar' => ['required', 'string', 'max:500'],
            'answer.fr' => ['required', 'string', 'max:5000'],
            'answer.ar' => ['required', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:100'],
            'position' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
