<?php

namespace App\Models;

use App\Enums\HomepageSectionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomepageSection extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'title', 'subtitle', 'content', 'image_path', 'button_text', 'button_url', 'settings', 'sort_order', 'active'];

    protected function casts(): array
    {
        return ['type' => HomepageSectionType::class, 'settings' => 'array', 'sort_order' => 'integer', 'active' => 'boolean'];
    }
}
