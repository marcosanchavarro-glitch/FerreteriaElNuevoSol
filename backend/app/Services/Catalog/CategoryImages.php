<?php

namespace App\Services\Catalog;

use App\Models\Category;
use App\Services\Audit\ActivityLogService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CategoryImages
{
    public function __construct(private ImageStorage $storage) {}

    public function replace(Category $category, ?UploadedFile $file): void
    {
        $folder = 'categories/'.$category->id;
        $newPath = $file ? $this->storage->store($file, $folder) : null;
        $oldPath = null;
        $backup = null;
        try {
            DB::transaction(function () use ($category, $folder, $newPath, &$oldPath, &$backup): void {
                Category::whereKey($category->id)->update(['updated_at' => now()]);
                $category->refresh();
                $audit = app(ActivityLogService::class);
                $before = $audit->snapshot($category);
                $oldPath = $category->image_path;
                if ($oldPath !== null) {
                    $backup = $this->storage->backup($oldPath, $folder);
                }
                $category->update(['image_path' => $newPath]);
                if ($oldPath !== null) {
                    $this->storage->delete($oldPath, $folder);
                }
                $audit->changed($category, $before, 'image');
            });
        } catch (\Throwable $exception) {
            if ($oldPath !== null && $backup !== null) {
                $this->storage->restore($oldPath, $folder, $backup);
            }
            if ($newPath !== null) {
                $this->storage->delete($newPath, $folder);
            }
            throw $exception;
        }
    }
}
