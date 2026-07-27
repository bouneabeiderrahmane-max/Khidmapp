<?php

namespace App\Http\Requests\Complaint;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

class StoreComplaintMessageRequest extends FormRequest
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
            'message' => ['nullable', 'string', 'max:2000'],
            'attachments' => ['sometimes', 'array', 'max:'.config('complaints.attachment_max_count')],
            'attachments.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('complaints.attachment_max_size_kb')],
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $hasMessage = trim((string) $this->input('message')) !== '';
            $hasAttachments = is_array($this->file('attachments')) && count($this->file('attachments')) > 0;

            if (! $hasMessage && ! $hasAttachments) {
                $validator->errors()->add('message', __('khidmapp.complaint_message_or_attachment_required'));
            }
        });
    }
}
