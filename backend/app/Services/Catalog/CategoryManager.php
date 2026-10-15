<?php

namespace App\Services\Catalog;

use App\Models\Category;
use App\Services\Audit\ActivityLogService;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CategoryManager
{
    /** @return array<int, array{category: Category, depth: int}> */
    public function tree(): array
    {
        $categories = Category::withCount('products')->orderBy('sort_order')->orderBy('name')->orderBy('id')->get();
        $children = $categories->groupBy(fn (Category $category): string => (string) ($category->parent_id ?? 'root'));
        $stack = [];
        foreach ($children->get('root', collect())->reverse() as $category) {
            $stack[] = [$category, 0];
        }
        $rows = [];
        $seen = [];
        while ($stack !== []) {
            [$category,$depth] = array_pop($stack);
            if (isset($seen[$category->id])) {
                continue;
            }
            $seen[$category->id] = true;
            $rows[] = ['category' => $category, 'depth' => $depth];
            foreach ($children->get((string) $category->id, collect())->reverse() as $child) {
                $stack[] = [$child, $depth + 1];
            }
        }

        return $rows;
    }

    public function save(Category $category, array $data): Category
    {
        try {
            return DB::transaction(function () use ($category, $data): Category {
                Category::query()->orderBy('id')->lockForUpdate()->get(['id', 'parent_id']);
                if ($category->exists) {
                    $category->refresh();
                }
                $audit = app(ActivityLogService::class);
                $before = $audit->snapshot($category);
                $action = $category->exists ? 'updated' : 'created';
                $category->fill($data)->save();
                $audit->changed($category, $before, $action);

                return $category;
            }, 5);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['parent_id' => 'La categoría padre no puede ser ella misma ni una de sus descendientes.']);
        }
    }
}
