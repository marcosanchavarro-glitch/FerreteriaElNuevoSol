<?php

namespace App\Models;

use App\Casts\MinorUnits;
use App\Casts\ScaledQuantity;
use App\Enums\ProductType;
use App\Enums\PurchaseMode;
use App\Enums\SaleUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'external_id', 'sku', 'name', 'value', 'sale_unit', 'purchase_mode', 'price', 'stock', 'active', 'external_synced_at'];

    protected function casts(): array
    {
        return ['sale_unit' => SaleUnit::class, 'purchase_mode' => PurchaseMode::class, 'price' => MinorUnits::class, 'stock' => ScaledQuantity::class, 'external_synced_at' => 'immutable_datetime', 'active' => 'boolean'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isExternallyManaged(): bool
    {
        return $this->external_id !== null || $this->external_synced_at !== null || $this->product->isExternallyManaged();
    }

    public function effectiveSaleUnit(): SaleUnit
    {
        return $this->sale_unit ?? $this->product->sale_unit;
    }

    public function effectivePurchaseMode(): PurchaseMode
    {
        return $this->purchase_mode ?? $this->product->purchase_mode;
    }

    protected static function booted(): void
    {
        static::saving(function (ProductVariant $variant) {
            if (Product::find($variant->product_id)?->product_type !== ProductType::Variable) {
                throw new \DomainException('Variants require a variable parent product.');
            }
        });
    }
}
