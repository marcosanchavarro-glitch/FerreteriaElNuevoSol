<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'message', 'type', 'created_by', 'published_at', 'expires_at', 'active', 'order_id'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('active', true)->whereNotNull('published_at')->where('published_at', '<=', now())
            ->where(fn (Builder $q): Builder => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    protected function casts(): array
    {
        return ['active' => 'boolean', 'published_at' => 'immutable_datetime', 'expires_at' => 'immutable_datetime'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'notification_user')->withPivot('read_at');
    }
}
