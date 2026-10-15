<?php

namespace App\Models;

use App\Casts\MinorUnits;
use App\Casts\ScaledQuantity;
use App\Enums\SaleUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'product_id', 'product_variant_id', 'product_name', 'variant_name', 'sku', 'sale_unit', 'quantity', 'unit_price', 'subtotal', 'requires_confirmation'];

    protected function casts(): array
    {
        return ['sale_unit' => SaleUnit::class, 'quantity' => ScaledQuantity::class, 'unit_price' => MinorUnits::class, 'subtotal' => MinorUnits::class, 'requires_confirmation' => 'boolean'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    protected static function booted(): void
    {
        static::saving(function (OrderItem $item) {
            if ((int) $item->getAttributes()['quantity'] <= 0) {
                throw new \DomainException('Order item quantity must be positive.');
            }
            if ($item->product_id !== null && $item->product_variant_id !== null) {
                $variant = ProductVariant::find($item->product_variant_id);
                if ($variant && (string) $variant->product_id !== (string) $item->product_id) {
                    throw new \DomainException('Order item variant must belong to the referenced product.');
                }
            }
        });
    }
}
