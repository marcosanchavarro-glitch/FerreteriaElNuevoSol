<?php

namespace App\Http\Requests;

use App\Enums\ProductType;
use App\Enums\PurchaseMode;
use App\Enums\SaleUnit;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class CatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('q'))) {
            $this->merge(['q' => preg_replace('/\s+/u', ' ', trim($this->input('q')))]);
        }
    }

    public function rules(): array
    {
        return ['q' => ['nullable', 'string', 'max:120'], 'categoria' => ['nullable', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/D'], 'disponibilidad' => ['nullable', Rule::in(['disponible', 'sin-stock', 'consultar'])], 'modalidad' => ['nullable', Rule::enum(PurchaseMode::class)], 'unidad' => ['nullable', Rule::enum(SaleUnit::class)], 'tipo' => ['nullable', Rule::enum(ProductType::class)], 'orden' => ['nullable', Rule::in(['relevancia', 'nombre-asc', 'nombre-desc', 'precio-asc', 'precio-desc'])], 'page' => ['nullable', 'integer', 'min:1', 'max:100000'], 'variante' => ['nullable', 'integer', 'min:1']];
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->expectsJson()) {
            parent::failedValidation($validator);
        }
        throw new HttpResponseException(response()->view('storefront.invalid', [], 422));
    }
}
