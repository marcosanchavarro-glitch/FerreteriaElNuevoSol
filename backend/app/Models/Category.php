<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['parent_id', 'name', 'slug', 'description', 'image_path', 'active', 'featured', 'sort_order'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'featured' => 'boolean', 'sort_order' => 'integer'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'category_product');
    }

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            if (! $category->isDirty('parent_id')) {
                return;
            }
            $seen = $category->exists ? [(string) $category->id => true] : [];
            $parentId = $category->parent_id;
            while ($parentId !== null) {
                if (isset($seen[(string) $parentId])) {
                    throw new \DomainException('Category hierarchy cannot contain cycles.');
                }
                $seen[(string) $parentId] = true;
                $parentId = static::query()->whereKey($parentId)->value('parent_id');
            }
        });
    }
}
