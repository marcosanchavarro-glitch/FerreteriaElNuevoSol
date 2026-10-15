<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImageUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('product') ?? $this->route('category')) && ! $this->hasAny(['id', 'product_id', 'category_id', 'path', 'image_path', 'is_primary', 'sort_order']);
    }

    public function rules(): array
    {
        return ['image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=1,min_height=1,max_width=3000,max_height=3000'], 'alt_text' => ['nullable', 'string', 'max:255']];
    }
}
