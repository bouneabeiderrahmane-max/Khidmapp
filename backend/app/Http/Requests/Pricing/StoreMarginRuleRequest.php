<?php

namespace App\Http\Requests\Pricing;

use App\Support\MarginScope;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StoreMarginRuleRequest extends FormRequest
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
            'scope_type' => ['required', Rule::in([MarginScope::GLOBAL, MarginScope::BOUTIQUE, MarginScope::CATEGORY])],
            'scope_id' => ['required_unless:scope_type,'.MarginScope::GLOBAL, 'nullable', 'integer'],
            'percent' => ['required', 'numeric', 'between:0,1000'],
            'effective_at' => ['sometimes', 'date'],
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $scopeType = $this->input('scope_type');
            $scopeId = $this->input('scope_id');

            if ($scopeType === MarginScope::GLOBAL || $scopeId === null) {
                return;
            }

            $table = $scopeType === MarginScope::BOUTIQUE ? 'boutiques' : 'categories';

            if (! DB::table($table)->where('id', $scopeId)->exists()) {
                $validator->errors()->add('scope_id', __('validation.exists', ['attribute' => 'scope_id']));
            }
        });
    }
}
