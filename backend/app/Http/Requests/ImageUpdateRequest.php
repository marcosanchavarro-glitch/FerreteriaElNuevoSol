<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImageUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('product')) && ! $this->hasAny(['id', 'product_id', 'path', 'is_primary', 'sort_order']);
    }

    public function rules(): array
    {
        return ['alt_text' => ['nullable', 'string', 'max:255']];
    }
}
