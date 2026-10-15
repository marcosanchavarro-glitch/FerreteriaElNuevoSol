<?php

namespace App\Services\Storefront;

use App\Models\Category;
use App\Models\ProductImage;
use App\Services\Catalog\ImageStorage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PublicImages
{
    public function available(?string $path, string $folder): bool
    {
        if ($path === null) {
            return false;
        }
        try {
            app(ImageStorage::class)->assertPath($path, $folder);
        } catch (ValidationException) {
            return false;
        }

        return config('catalog.storage') === 'cloudinary' || Storage::disk('catalog')->exists($path);
    }

    public function product(?ProductImage $image): string
    {
        return $image && $this->available($image->path, 'products/'.$image->product_id) ? route('store.images.product', $image->id) : asset('images/product-placeholder.svg');
    }

    public function category(Category $category): string
    {
        return $this->available($category->image_path, 'categories/'.$category->id) ? route('store.images.category', $category->slug) : asset('images/product-placeholder.svg');
    }
}
