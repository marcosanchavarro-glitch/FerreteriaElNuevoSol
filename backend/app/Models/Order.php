<?php

namespace App\Models;

use App\Casts\MinorUnits;
use App\Enums\FulfillmentMethod;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['order_number', 'user_id', 'customer_first_name', 'customer_last_name', 'customer_phone', 'customer_email', 'status', 'subtotal', 'confirmed_total', 'has_items_to_confirm', 'fulfillment_method'];

    protected function casts(): array
    {
        return ['status' => OrderStatus::class, 'subtotal' => MinorUnits::class, 'confirmed_total' => MinorUnits::class, 'has_items_to_confirm' => 'boolean', 'fulfillment_method' => FulfillmentMethod::class];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }
}
