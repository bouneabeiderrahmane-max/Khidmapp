<?php

namespace App\Http\Requests\AdminUser;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRolesRequest extends FormRequest
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
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [Rule::in([Roles::SERVICE_CLIENT, Roles::ADMINISTRATEUR])],
        ];
    }
}
