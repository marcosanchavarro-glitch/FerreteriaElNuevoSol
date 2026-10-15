<?php

namespace App\Services\Storefront;

use App\Models\Category;
use Illuminate\Support\Collection;

class PublicCategories
{
    private ?Collection $visible = null;

    private array $rows = [];

    public function all(): Collection
    {
        if ($this->visible !== null) {
            return $this->visible;
        }
        $groups = Category::orderBy('sort_order')->orderBy('name')->orderBy('id')->get()->groupBy(fn (Category $c): string => (string) ($c->parent_id ?? 'root'));
        $this->visible = collect();
        $stack = [];
        foreach ($groups->get('root', collect())->reverse() as $c) {
            $stack[] = [$c, 0];
        }
        while ($stack !== []) {
            [$c,$depth] = array_pop($stack);
            if (! $c->active || $this->visible->has($c->id)) {
                continue;
            }
            $this->visible->put($c->id, $c);
            $this->rows[] = ['category' => $c, 'depth' => $depth];
            foreach ($groups->get((string) $c->id, collect())->reverse() as $child) {
                $stack[] = [$child, $depth + 1];
            }
        }

        return $this->visible;
    }

    public function rows(): array
    {
        $this->all();

        return $this->rows;
    }

    public function find(string $slug): Category
    {
        return $this->all()->firstWhere('slug', $slug) ?? abort(404);
    }

    public function descendants(Category $category): array
    {
        $ids = [$category->id];
        $groups = $this->all()->groupBy('parent_id');
        $stack = [$category->id];
        while ($stack !== []) {
            foreach ($groups->get(array_pop($stack), collect()) as $child) {
                $ids[] = $child->id;
                $stack[] = $child->id;
            }
        }

        return $ids;
    }

    public function ancestors(Category $category): array
    {
        $chain = [];
        $cursor = $category;
        while ($cursor !== null) {
            array_unshift($chain, $cursor);
            $cursor = $this->all()->get($cursor->parent_id);
        }

        return $chain;
    }
}
