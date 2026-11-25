<?php

namespace App\Services\Content;

use App\Models\Banner;
use App\Models\HomepageSection;
use App\Services\Storefront\CatalogQuery;
use App\Services\Storefront\PublicCategories;
use Illuminate\Database\Eloquent\Collection;

class Homepage
{
    public static function defaults(): array
    {
        return [
            ['type' => 'hero', 'title' => 'Todo lo que tu obra necesita.', 'subtitle' => 'Herramientas para hacer bien el trabajo', 'content' => 'Soluciones para profesionales, talleres y proyectos de todos los días.', 'button_text' => 'Ver catálogo', 'button_url' => '/productos'],
            ['type' => 'featured_categories', 'title' => 'Encontrá lo que necesitás', 'settings' => ['max_items' => 8]],
            ['type' => 'featured_products', 'title' => 'Productos destacados', 'settings' => ['max_items' => 6]],
            ['type' => 'banner', 'title' => 'Novedades de la ferretería'],
            ['type' => 'promotion', 'title' => 'Calidad sin vueltas', 'content' => 'Herramientas y materiales para acompañar tus proyectos.', 'button_text' => 'Conocé el catálogo', 'button_url' => '/productos'],
            ['type' => 'text_image', 'title' => '¿Buscás algo puntual?', 'content' => 'Buscá por nombre, código o categoría y encontrá la presentación que necesitás.', 'button_text' => 'Buscar productos', 'button_url' => '/productos'],
        ];
    }

    public function data(CatalogQuery $catalog, PublicCategories $categories, HomepageSection|Banner|null $draft = null): array
    {
        $sections = HomepageSection::where('active', true)->orderBy('sort_order')->orderBy('id')->get();
        if ($draft instanceof HomepageSection) {
            $sections = $sections->reject(fn ($section) => $draft->exists && $section->id === $draft->id);
            if ($draft->active) {
                $sections->push($draft);
            }
            $sections = $sections->sortBy([['sort_order', 'asc'], ['id', 'asc']])->values();
        }
        if ($sections->isEmpty() && ! HomepageSection::exists() && ! $draft instanceof HomepageSection) {
            $sections = collect(self::defaults())->map(fn (array $data, int $i): HomepageSection => new HomepageSection($data + ['active' => true, 'sort_order' => $i]));
        }
        $max = max(1, min(24, (int) $sections->filter(fn ($s) => $s->type->value === 'featured_products')->max(fn ($s) => (int) ($s->settings['max_items'] ?? 6))));

        $banners = Banner::where('active', true)->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))->orderBy('sort_order')->orderBy('id')->get();
        if ($draft instanceof Banner) {
            $banners = $banners->reject(fn ($banner) => $draft->exists && $banner->id === $draft->id);
            if ($draft->active && (! $draft->starts_at || $draft->starts_at->lte(now())) && (! $draft->ends_at || $draft->ends_at->gt(now()))) {
                $banners->push($draft);
            }
            $banners = $banners->sortBy([['sort_order', 'asc'], ['id', 'asc']])->values();
        }

        $featuredCategories = new Collection($categories->all()->where('featured', true)->values()->all());
        $featuredCategories->load(['products' => fn ($query) => $query->where('status', 'published')->whereHas('images')->orderBy('products.id')->limit(1)->with(['images' => fn ($images) => $images->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id')->limit(1)])]);

        return [
            'sections' => $sections,
            'banners' => $banners,
            'products' => $catalog->base($categories->all()->keys()->all())->where('featured', true)->orderByDesc('id')->limit($max)->get(),
            'featuredCategories' => $featuredCategories,
        ];
    }
}
