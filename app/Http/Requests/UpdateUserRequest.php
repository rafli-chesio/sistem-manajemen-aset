<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('user.edit');
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'password'   => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
            'nip'        => ['nullable', 'string', 'max:30'],
            'department' => ['nullable', 'array'],
            'department.*' => ['string', 'max:100'],
            'role'       => ['required', Rule::in(['super_admin', 'viewer', 'kajur'])],
        ];
    }
}
