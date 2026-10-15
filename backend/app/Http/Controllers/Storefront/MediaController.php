<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\ProductImage;
use App\Services\Catalog\ImageStorage;
use App\Services\Storefront\PublicCategories;
use App\Services\Storefront\PublicImages;
use Symfony\Component\HttpFoundation\Response;

class MediaController extends Controller
{
    public function __construct(private PublicImages $images, private ImageStorage $storage) {}

    private function serve(?string $path, string $folder): Response
    {
        if (! $this->images->available($path, $folder)) {
            return response()->file(public_path('images/product-placeholder.svg'), ['Content-Type' => 'image/svg+xml', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
        }

        return $this->storage->response($path, $folder);
    }

    public function product(int $image): Response
    {
        $record = ProductImage::whereHas('product', fn ($q) => $q->where('status', 'published'))->findOrFail($image);

        return $this->serve($record->path, 'products/'.$record->product_id);
    }

    public function category(string $slug, PublicCategories $categories): Response
    {
        $category = $categories->find($slug);

        return $this->serve($category->image_path, 'categories/'.$category->id);
    }
}
