<?php

namespace App\Http\Requests\Auth;

use App\Rules\PasswordInput;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends PasswordEmailRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'token' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:72', new PasswordInput, 'confirmed', Password::min(12)->letters()->numbers()],
        ]);
    }
}
