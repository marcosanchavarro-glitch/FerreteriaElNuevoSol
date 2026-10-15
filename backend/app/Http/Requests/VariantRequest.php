<?php

namespace App\Http\Requests;

use App\Enums\PurchaseMode;
use App\Enums\SaleUnit;
use App\Models\ProductVariant;
use App\Support\ProductDisplay;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class VariantRequest extends FormRequest
{
    public function variant(): ProductVariant
    {
        return $this->route('variant') ?? (new ProductVariant)->setRelation('product', $this->route('product'));
    }

    public function authorize(): bool
    {
        $variant = $this->variant();
        if (! $this->user()?->can($variant->exists ? 'update' : 'create', $variant->exists ? $variant : [ProductVariant::class, $this->route('product')])) {
            return false;
        }
        if ($this->hasAny(['id', 'product_id', 'external_id', 'external_synced_at', 'created_at', 'updated_at', 'role', 'user_id'])) {
            return false;
        }

        return $this->user()->can('updateCommercial', $variant) || ! $this->hasAny(['sku', 'price', 'stock']);
    }

    public function rules(): array
    {
        $rules = ['name' => ['required', 'string', 'max:255'], 'value' => ['nullable', 'string', 'max:255'], 'sale_unit' => ['nullable', Rule::enum(SaleUnit::class)], 'purchase_mode' => ['nullable', Rule::enum(PurchaseMode::class)], 'active' => ['required', 'boolean']];
        if ($this->user()->can('updateCommercial', $this->variant())) {
            $rules += ['sku' => ['nullable', 'string', 'max:255', Rule::unique('product_variants', 'sku')->where('product_id', $this->route('product')->id)->ignore($this->variant()->id)], 'price' => ['nullable', 'string', 'regex:/^\d{1,13}(?:[.,]\d{1,2})?$/D'], 'stock' => ['nullable', 'string', 'regex:/^\d{1,12}(?:[.,]\d{1,3})?$/D']];
        }

        return $rules;
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $mode = $this->has('purchase_mode')
                ? ($this->input('purchase_mode') ?? $this->route('product')->purchase_mode->value)
                : $this->variant()->effectivePurchaseMode()->value;
            $price = $this->has('price') ? $this->input('price') : $this->variant()->price;
            if ($this->boolean('active') && $mode === PurchaseMode::Calculated->value && $price === null) {
                $validator->errors()->add('price', 'Una variante activa con compra calculable necesita precio. Elegí Requiere confirmación o guardala inactiva.');
            }
        }];
    }

    public function variantData(): array
    {
        $data = $this->validated();
        if (isset($data['price'])) {
            $data['price'] = ProductDisplay::toMinorUnits($data['price']);
        }
        if (isset($data['stock'])) {
            $data['stock'] = str_replace(',', '.', $data['stock']);
        }

        return $data;
    }
}
