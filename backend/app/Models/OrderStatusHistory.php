<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    use HasFactory;

    protected $fillable = ['order_id', 'previous_status', 'new_status', 'changed_by'];

    protected function casts(): array
    {
        return ['previous_status' => OrderStatus::class, 'new_status' => OrderStatus::class, 'created_at' => 'immutable_datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    protected $table = 'order_status_history';

    public const UPDATED_AT = null;
}
