<?php

namespace App\Http\Requests;

use App\Rules\PasswordInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->active && ! $this->hasAny(['id', 'user_id', 'role', 'active', 'email', 'email_verified_at', 'remember_token']);
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', new PasswordInput, 'current_password:web'],
            'password' => ['required', 'string', 'max:72', new PasswordInput, 'confirmed', Password::min(12)->letters()->numbers()],
        ];
    }
}
