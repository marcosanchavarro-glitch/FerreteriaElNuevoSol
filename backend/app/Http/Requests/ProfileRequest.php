<?php

namespace App\Http\Requests;

use App\Rules\PasswordInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->active && ! $this->hasAny(['id', 'user_id', 'role', 'active', 'email_verified_at', 'password', 'remember_token']);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'], 'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($this->user()->id)],
            'current_password' => [Rule::requiredIf(fn () => $this->input('email') !== $this->user()->email), 'nullable', 'string', new PasswordInput, 'current_password:web'],
        ];
    }
}
