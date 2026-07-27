<?php

namespace App\Http\Requests\Complaint;

use App\Support\ComplaintCategory;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComplaintRequest extends FormRequest
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
            'order_id' => ['required', 'integer'],
            'category' => ['required', Rule::in(ComplaintCategory::all())],
            'message' => ['required', 'string', 'max:2000'],
            'attachments' => ['sometimes', 'array', 'max:'.config('complaints.attachment_max_count')],
            'attachments.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('complaints.attachment_max_size_kb')],
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $orderId = $this->input('order_id');

            if ($orderId === null) {
                return;
            }

            $belongsToUser = $this->user()->orders()->whereKey($orderId)->exists();

            if (! $belongsToUser) {
                $validator->errors()->add('order_id', __('khidmapp.order_not_found'));
            }
        });
    }
}
