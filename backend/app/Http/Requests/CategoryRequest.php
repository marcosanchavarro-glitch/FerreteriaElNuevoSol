<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $category = $this->route('category');

        return $this->user()?->can($category ? 'update' : 'create', $category ?? Category::class) && ! $this->hasAny(['id', 'image_path', 'created_at', 'updated_at']);
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'slug' => ['required', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', Rule::unique('categories')->ignore($this->route('category')?->id)], 'parent_id' => ['nullable', 'integer', 'exists:categories,id'], 'description' => ['nullable', 'string', 'max:10000'], 'active' => ['required', 'boolean'], 'featured' => ['required', 'boolean'], 'sort_order' => ['required', 'integer', 'min:0', 'max:1000000']];
    }
}
