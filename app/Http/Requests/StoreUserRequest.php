<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('user.create');
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'unique:users,email'],
            'password'   => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'nip'        => ['nullable', 'string', 'max:30'],
            'department' => ['nullable', 'string', 'max:100'],
            'role'       => ['required', Rule::in(['super_admin', 'viewer', 'kajur'])],
        ];
    }
}
