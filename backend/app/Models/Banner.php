<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'subtitle', 'image_path', 'button_text', 'button_url', 'starts_at', 'ends_at', 'active', 'sort_order'];

    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime', 'active' => 'boolean', 'sort_order' => 'integer'];
    }
}
