<?php

namespace App\Http\Requests\AdminUser;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CreateInternalUserRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)],
            // Client s'inscrit lui-même (OTP/e-mail) : ce point d'entrée ne
            // crée que des comptes internes (8.9.2).
            'role' => ['required', Rule::in([Roles::SERVICE_CLIENT, Roles::ADMINISTRATEUR])],
        ];
    }
}
