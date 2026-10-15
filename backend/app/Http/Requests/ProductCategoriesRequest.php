<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductCategoriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('product')) && ! $this->hasAny(['id', 'product_id']);
    }

    public function rules(): array
    {
        return ['category_ids' => ['sometimes', 'array', 'max:1000'], 'category_ids.*' => ['required', 'integer', 'distinct', 'exists:categories,id']];
    }
}
