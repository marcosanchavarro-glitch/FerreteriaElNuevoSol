<?php

namespace App\Services\Content;

use App\Models\Banner;
use App\Models\HomepageSection;
use App\Services\Audit\ActivityLogService;
use App\Services\Catalog\ImageStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ContentManager
{
    public function __construct(private ImageStorage $storage) {}

    public function save(HomepageSection|Banner $record, array $data, ?UploadedFile $file): void
    {
        $newPath = $oldPath = $backup = null;
        $folder = null;
        try {
            DB::transaction(function () use ($record, $data, $file, &$newPath, &$oldPath, &$backup, &$folder): void {
                if ($record->exists) {
                    $record->newQuery()->whereKey($record->id)->update(['updated_at' => now()]);
                    $record->refresh();
                } elseif ($record instanceof Banner) {
                    $record->image_path = '';
                }
                $audit = app(ActivityLogService::class);
                $before = $audit->snapshot($record);
                $action = $record->exists ? 'updated' : 'created';
                $record->fill($data)->save();
                $folder = ($record instanceof Banner ? 'banners/' : 'sections/').$record->id;
                if ($file) {
                    $newPath = $this->storage->store($file, $folder);
                    $oldPath = $record->image_path ?: null;
                    if ($oldPath) {
                        $backup = $this->storage->backup($oldPath, $folder);
                    }
                    $record->update(['image_path' => $newPath]);
                    if ($oldPath) {
                        $this->storage->delete($oldPath, $folder);
                    }
                }
                $audit->changed($record, $before, $action);
            });
        } catch (\Throwable $error) {
            if ($oldPath && $backup !== null) {
                $this->storage->restore($oldPath, $folder, $backup);
            }
            if ($newPath) {
                $this->storage->delete($newPath, $folder);
            }
            throw $error;
        }
    }

    public function removeImage(HomepageSection $record): void
    {
        $folder = 'sections/'.$record->id;
        $oldPath = $backup = null;
        try {
            DB::transaction(function () use ($record, $folder, &$oldPath, &$backup): void {
                $record->newQuery()->whereKey($record->id)->update(['updated_at' => now()]);
                $record->refresh();
                $audit = app(ActivityLogService::class);
                $before = $audit->snapshot($record);
                $action = 'image';
                $oldPath = $record->image_path;
                if ($oldPath) {
                    $backup = $this->storage->backup($oldPath, $folder);
                }
                $record->update(['image_path' => null]);
                if ($oldPath) {
                    $this->storage->delete($oldPath, $folder);
                }
                $audit->changed($record, $before, $action);
            });
        } catch (\Throwable $error) {
            if ($oldPath && $backup !== null) {
                $this->storage->restore($oldPath, $folder, $backup);
            }
            throw $error;
        }
    }

    public function move(HomepageSection|Banner $record, string $direction): void
    {
        DB::transaction(function () use ($record, $direction): void {
            $record->newQuery()->whereKey($record->id)->update(['updated_at' => now()]);
            $audit = app(ActivityLogService::class);
            $before = $audit->order($record);
            $ids = $record->newQuery()->orderBy('sort_order')->orderBy('id')->pluck('id')->all();
            $position = array_search($record->id, $ids, true);
            $other = $position + ($direction === 'up' ? -1 : 1);
            if (! isset($ids[$other])) {
                return;
            }
            [$ids[$position], $ids[$other]] = [$ids[$other], $ids[$position]];
            foreach ($ids as $order => $id) {
                $record->newQuery()->whereKey($id)->update(['sort_order' => $order]);
            }
            $audit->record($audit->module($record), 'reordered', $record, $before, $audit->order($record));
        });
    }
}
