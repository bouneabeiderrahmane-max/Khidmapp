<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferencesRequest extends FormRequest
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
            'push' => ['sometimes', 'boolean'],
            'sms' => ['sometimes', 'boolean'],
            'email' => ['sometimes', 'boolean'],
        ];
    }
}
