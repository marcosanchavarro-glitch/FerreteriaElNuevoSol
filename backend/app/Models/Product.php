<?php

namespace App\Models;

use App\Casts\MinorUnits;
use App\Casts\ScaledQuantity;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\PurchaseMode;
use App\Enums\SaleUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    public function isExternallyManaged(): bool
    {
        return $this->external_id !== null || $this->external_synced_at !== null;
    }

    protected $fillable = ['external_id', 'sku', 'name', 'slug', 'short_description', 'description', 'product_type', 'sale_unit', 'purchase_mode', 'price', 'stock', 'status', 'featured', 'external_synced_at'];

    protected function casts(): array
    {
        return ['sale_unit' => SaleUnit::class, 'purchase_mode' => PurchaseMode::class, 'price' => MinorUnits::class, 'stock' => ScaledQuantity::class, 'external_synced_at' => 'immutable_datetime', 'product_type' => ProductType::class, 'status' => ProductStatus::class, 'featured' => 'boolean'];
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product');
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if ($product->product_type === ProductType::Variable && ($product->price !== null || $product->stock !== null)) {
                throw new \DomainException('Variable products store price and stock only on variants.');
            }
            if ($product->exists && $product->isDirty('product_type') && $product->product_type === ProductType::Simple && $product->variants()->exists()) {
                throw new \DomainException('A product with variants cannot become simple.');
            }
        });
    }
}
