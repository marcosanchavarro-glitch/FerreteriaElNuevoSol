<?php

namespace App\Http\Requests;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\PurchaseMode;
use App\Enums\SaleUnit;
use App\Models\Product;
use App\Support\ProductDisplay;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProductRequest extends FormRequest
{
    public function product(): Product
    {
        return $this->route('product') ?? new Product;
    }

    public function authorize(): bool
    {
        $product = $this->product();
        if (! $this->user()?->can($product->exists ? 'update' : 'create', $product)) {
            return false;
        }
        if ($this->hasAny(['id', 'user_id', 'role', 'active', 'external_id', 'external_synced_at', 'created_at', 'updated_at'])) {
            return false;
        }
        if (! $this->user()->can('updateCommercial', $product) && $this->hasAny(['price', 'stock', 'sku'])) {
            return false;
        }
        if (! $this->user()->can('updateIdentity', $product) && $this->hasAny(['name', 'product_type', 'sale_unit'])) {
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        $product = $this->product();
        $rules = [
            'slug' => ['required', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', Rule::unique('products', 'slug')->ignore($product->id)],
            'short_description' => ['nullable', 'string', 'max:500'], 'description' => ['nullable', 'string', 'max:20000'],
            'purchase_mode' => ['required', Rule::enum(PurchaseMode::class)], 'status' => ['required', Rule::enum(ProductStatus::class)], 'featured' => ['required', 'boolean'],
        ];
        if ($this->user()->can('updateIdentity', $product)) {
            $rules += ['name' => ['required', 'string', 'max:255'], 'product_type' => ['required', Rule::enum(ProductType::class)], 'sale_unit' => ['required', Rule::enum(SaleUnit::class)]];
        }
        if ($this->user()->can('updateCommercial', $product)) {
            $rules += ['sku' => ['nullable', 'string', 'max:255'], 'price' => ['nullable', 'string', 'regex:/^\d{1,13}(?:[.,]\d{1,2})?$/D'], 'stock' => ['nullable', 'string', 'regex:/^\d{1,12}(?:[.,]\d{1,3})?$/D']];
        }

        return $rules;
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $product = $this->product();
            $type = $this->input('product_type', $product->product_type?->value);
            $price = $this->has('price') ? ($this->input('price') === null ? null : ProductDisplay::toMinorUnits($this->input('price'))) : $product->price;
            $stock = $this->has('stock') ? $this->input('stock') : $product->stock;
            if ($type === ProductType::Variable->value && ($price !== null || $stock !== null)) {
                $validator->errors()->add('product_type', 'Un producto con variantes guarda precio y stock en cada variante. Dejá vacíos los importes del padre.');
            }
            if ($product->exists && $type === ProductType::Simple->value && $product->variants()->exists()) {
                $validator->errors()->add('product_type', 'Este producto tiene variantes y no puede convertirse en simple.');
            }
            if ($product->exists && $type === ProductType::Variable->value && $this->input('purchase_mode') === PurchaseMode::Calculated->value && $product->purchase_mode !== PurchaseMode::Calculated && $product->variants()->where('active', true)->whereNull('purchase_mode')->whereNull('price')->exists()) {
                $validator->errors()->add('purchase_mode', 'Hay variantes activas sin precio que heredan esta modalidad. Completá sus precios o elegí Requiere confirmación en esas variantes antes de cambiar el producto.');
            }
            if ($type === ProductType::Simple->value && $this->input('status') === ProductStatus::Published->value && $this->input('purchase_mode') === PurchaseMode::Calculated->value && $price === null) {
                $validator->errors()->add('status', 'Para publicar un producto calculable se necesita precio. Podés guardarlo en borrador o elegir Requiere confirmación.');
            }
        }];
    }

    public function productData(): array
    {
        $data = $this->validated();
        if (array_key_exists('price', $data) && $data['price'] !== null) {
            $data['price'] = ProductDisplay::toMinorUnits($data['price']);
        }
        if (array_key_exists('stock', $data) && $data['stock'] !== null) {
            $data['stock'] = str_replace(',', '.', $data['stock']);
        }

        return $data;
    }
}
