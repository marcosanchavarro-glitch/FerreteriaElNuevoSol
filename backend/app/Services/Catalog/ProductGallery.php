<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Audit\ActivityLogService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ProductGallery
{
    public function __construct(private ImageStorage $storage) {}

    /** The first write serializes gallery changes on SQLite and row-locking databases. */
    private function lock(Product $product): void
    {
        Product::whereKey($product->id)->update(['updated_at' => now()]);
    }

    public function upload(Product $product, UploadedFile $file, ?string $alt): ProductImage
    {
        $folder = 'products/'.$product->id;
        $path = $this->storage->store($file, $folder);
        try {
            return DB::transaction(function () use ($product, $path, $alt): ProductImage {
                $this->lock($product);
                $audit = app(ActivityLogService::class);
                $before = $audit->gallery($product);

                $created = $product->images()->create(['path' => $path, 'alt_text' => $alt, 'is_primary' => ! $product->images()->where('is_primary', true)->exists(), 'sort_order' => ($product->images()->max('sort_order') ?? -1) + 1]);
                $audit->record('images', 'uploaded', $product->id, $before, $audit->gallery($product));

                return $created;
            }, 5);
        } catch (\Throwable $exception) {
            $this->storage->delete($path, $folder);
            throw $exception;
        }
    }

    public function primary(Product $product, ProductImage $image): void
    {
        DB::transaction(function () use ($product, $image): void {
            $this->lock($product);
            $audit = app(ActivityLogService::class);
            $before = $audit->gallery($product);
            $target = $product->images()->findOrFail($image->id);
            $product->images()->where('is_primary', true)->update(['is_primary' => false]);
            $target->update(['is_primary' => true]);
            $audit->record('images', 'primary', $product->id, $before, $audit->gallery($product));
        }, 5);
    }

    public function move(Product $product, ProductImage $image, string $direction): void
    {
        DB::transaction(function () use ($product, $image, $direction): void {
            $this->lock($product);
            $audit = app(ActivityLogService::class);
            $before = $audit->gallery($product);
            $images = $product->images()->orderBy('sort_order')->orderBy('id')->get()->all();
            $index = array_search($image->id, array_map(fn (ProductImage $item): int => $item->id, $images), true);
            abort_if($index === false, 404);
            $next = $index + ($direction === 'up' ? -1 : 1);
            if (isset($images[$next])) {
                [$images[$index],$images[$next]] = [$images[$next], $images[$index]];
            }
            foreach ($images as $order => $item) {
                $item->update(['sort_order' => $order]);
            }
            $audit->record('images', 'reordered', $product->id, $before, $audit->gallery($product));
        }, 5);
    }

    public function delete(Product $product, ProductImage $image): void
    {
        $folder = 'products/'.$product->id;
        $path = null;
        $backup = null;
        try {
            DB::transaction(function () use ($product, $image, $folder, &$path, &$backup): void {
                $this->lock($product);
                $audit = app(ActivityLogService::class);
                $before = $audit->gallery($product);
                $target = $product->images()->findOrFail($image->id);
                $path = $target->path;
                $backup = $this->storage->backup($path, $folder);
                $wasPrimary = $target->is_primary;
                $target->delete();
                if ($wasPrimary) {
                    $product->images()->where('is_primary', true)->update(['is_primary' => false]);
                    $product->images()->orderBy('sort_order')->orderBy('id')->first()?->update(['is_primary' => true]);
                }
                $this->storage->delete($path, $folder);
                $audit->record('images', 'deleted', $product->id, $before, $audit->gallery($product));
            });
        } catch (\Throwable $exception) {
            if ($path !== null && $backup !== null) {
                $this->storage->restore($path, $folder, $backup);
            }
            throw $exception;
        }
    }
}
