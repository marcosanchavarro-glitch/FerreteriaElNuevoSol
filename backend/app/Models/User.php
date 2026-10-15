<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\RoutesNotifications;

class User extends Authenticatable
{
    use HasFactory, RoutesNotifications;

    protected $fillable = ['first_name', 'last_name', 'email', 'phone', 'password'];

    protected function casts(): array
    {
        return ['role' => UserRole::class, 'password' => 'hashed', 'active' => 'boolean', 'email_verified_at' => 'immutable_datetime'];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function notifications(): BelongsToMany
    {
        return $this->belongsToMany(Notification::class, 'notification_user')->withPivot('read_at');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function createdNotifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'created_by');
    }

    public function statusChanges(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'changed_by');
    }

    protected $hidden = ['password', 'remember_token'];
}
