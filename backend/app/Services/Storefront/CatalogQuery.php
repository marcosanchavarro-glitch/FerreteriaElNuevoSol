<?php

namespace App\Services\Storefront;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CatalogQuery
{
    /** Only fixed SQL fragments; all user input is bound as query parameters. */
    private function fold(string $column): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction('catalog_fold', fn (?string $value): string => mb_strtolower($value ?? ''), 1);

            return 'catalog_fold('.$column.')';
        }

        return 'LOWER('.$column.')';
    }

    public function base(array $categoryIds): Builder
    {
        return Product::query()->where('status', 'published')->with([
            'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id'),
            'variants' => fn ($q) => $q->where('active', true)->orderBy('id'),
            'categories' => fn ($q) => $q->whereIn('categories.id', $categoryIds)->orderBy('sort_order')->orderBy('name'),
        ]);
    }

    public function catalog(array $filters, PublicCategories $categories, ?array $categoryIds = null): Builder
    {
        $visibleIds = $categories->all()->keys()->all();
        $query = $this->base($visibleIds);
        if ($categoryIds !== null) {
            $query->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds));
        }
        if (! empty($filters['tipo'])) {
            $query->where('product_type', $filters['tipo']);
        }
        $offerFilters = array_filter(array_intersect_key($filters, array_flip(['disponibilidad', 'modalidad', 'unidad'])), fn ($v) => $v !== null && $v !== '');
        if ($offerFilters !== []) {
            $query->where(function (Builder $q) use ($offerFilters): void {
                $q->where(function (Builder $simple) use ($offerFilters): void {
                    $simple->where('product_type', 'simple');
                    $this->offer($simple, $offerFilters, false);
                })
                    ->orWhere(function (Builder $variable) use ($offerFilters): void {
                        $variable->where('product_type', 'variable')->whereHas('variants', function (Builder $variant) use ($offerFilters): void {
                            $variant->where('active', true);
                            $this->offer($variant, $offerFilters, true);
                        });
                    });
            });
        }
        $term = $filters['q'] ?? '';
        if ($term !== '') {
            foreach (explode(' ', mb_strtolower($term)) as $token) {
                $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $token).'%';
                $query->where(function (Builder $q) use ($like, $visibleIds): void {
                    foreach (['products.name', 'products.sku', 'products.short_description', 'products.description'] as $column) {
                        $q->orWhereRaw($this->fold($column)." LIKE ? ESCAPE '!'", [$like]);
                    }
                    $q->orWhereHas('variants', function (Builder $v) use ($like): void {
                        $v->where('active', true)->where(function (Builder $v) use ($like): void {
                            foreach (['product_variants.name', 'product_variants.value', 'product_variants.sku'] as $column) {
                                $v->orWhereRaw($this->fold($column)." LIKE ? ESCAPE '!'", [$like]);
                            }
                        });
                    })->orWhereHas('categories', fn ($c) => $c->whereIn('categories.id', $visibleIds)->whereRaw($this->fold('categories.name')." LIKE ? ESCAPE '!'", [$like]));
                });
            }
        }
        $query->select('products.*')->selectRaw("CASE WHEN products.product_type = 'simple' AND products.purchase_mode = 'calculated' THEN products.price WHEN products.product_type = 'variable' THEN (SELECT MIN(v.price) FROM product_variants v WHERE v.product_id = products.id AND v.active = true AND COALESCE(v.purchase_mode, products.purchase_mode) = 'calculated') ELSE NULL END AS catalog_price");
        $order = $filters['orden'] ?? ($term !== '' ? 'relevancia' : 'nombre-asc');
        if (in_array($order, ['precio-asc', 'precio-desc'], true)) {
            $query->orderByRaw('catalog_price '.($order === 'precio-asc' ? 'ASC' : 'DESC').' NULLS LAST');
        } elseif ($order === 'relevancia' && $term !== '') {
            $exact = mb_strtolower($term);
            $prefix = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $exact).'%';
            $query->orderByRaw('CASE WHEN '.$this->fold('products.sku').' = ? THEN 0 WHEN '.$this->fold('products.name').' = ? THEN 1 WHEN '.$this->fold('products.name')." LIKE ? ESCAPE '!' THEN 2 ELSE 3 END", [$exact, $exact, $prefix]);
        }
        $query->orderByRaw($this->fold('products.name').($order === 'nombre-desc' ? ' DESC' : ' ASC'))->orderBy('products.id');

        return $query;
    }

    private function offer(Builder $query, array $filters, bool $variant): void
    {
        $prefix = $variant ? 'product_variants.' : 'products.';
        if (! empty($filters['disponibilidad'])) {
            match ($filters['disponibilidad']) {
                'disponible' => $query->where($prefix.'stock', '>', 0),
                'sin-stock' => $query->where($prefix.'stock', '<=', 0),
                'consultar' => $query->whereNull($prefix.'stock'),
            };
        }
        foreach (['modalidad' => 'purchase_mode', 'unidad' => 'sale_unit'] as $filter => $column) {
            if (! empty($filters[$filter])) {
                if ($variant) {
                    $query->whereRaw('COALESCE(product_variants.'.$column.', products.'.$column.') = ?', [$filters[$filter]]);
                } else {
                    $query->where($prefix.$column, $filters[$filter]);
                }
            }
        }
    }
}
