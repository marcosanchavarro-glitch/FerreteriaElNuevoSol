<?php

namespace App\Services\Catalog;

use App\Enums\PurchaseMode;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Audit\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class VariantManager
{
    public function save(Product $product, ?ProductVariant $variant, array $data): ProductVariant
    {
        return DB::transaction(function () use ($product, $variant, $data): ProductVariant {
            Product::whereKey($product->id)->update(['updated_at' => now()]);
            $product->refresh();
            $target = $variant ? $product->variants()->findOrFail($variant->id) : $product->variants()->make();
            $target->setRelation('product', $product);
            Gate::authorize($target->exists ? 'update' : 'create', $target->exists ? $target : [ProductVariant::class, $product]);
            if (array_intersect(['sku', 'price', 'stock'], array_keys($data))) {
                Gate::authorize('updateCommercial', $target);
            }
            if (isset($data['sku']) && $product->variants()->where('sku', $data['sku'])->when($target->exists, fn ($query) => $query->where('id', '!=', $target->id))->exists()) {
                throw ValidationException::withMessages(['sku' => 'Este SKU ya existe en otra variante del producto.']);
            }
            $audit = app(ActivityLogService::class);
            $before = $audit->snapshot($target);
            $action = $target->exists ? 'updated' : 'created';
            $target->fill($data);
            if ($target->active && $target->effectivePurchaseMode() === PurchaseMode::Calculated && $target->price === null) {
                throw ValidationException::withMessages(['price' => 'Una variante activa con compra calculable necesita precio.']);
            }
            $target->save();
            $audit->changed($target, $before, $action);

            return $target;
        }, 5);
    }
}
