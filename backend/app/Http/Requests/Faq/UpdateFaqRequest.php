<?php

namespace App\Http\Requests\Faq;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFaqRequest extends FormRequest
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
            'question.fr' => ['sometimes', 'string', 'max:500'],
            'question.ar' => ['sometimes', 'string', 'max:500'],
            'answer.fr' => ['sometimes', 'string', 'max:5000'],
            'answer.ar' => ['sometimes', 'string', 'max:5000'],
            'category' => ['sometimes', 'nullable', 'string', 'max:100'],
            'position' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
