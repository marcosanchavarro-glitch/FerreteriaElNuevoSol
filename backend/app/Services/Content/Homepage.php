<?php

namespace App\Services\Content;

use App\Models\Banner;
use App\Models\HomepageSection;
use App\Services\Storefront\CatalogQuery;
use App\Services\Storefront\PublicCategories;

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

    public function data(CatalogQuery $catalog, PublicCategories $categories): array
    {
        $sections = HomepageSection::where('active', true)->orderBy('sort_order')->orderBy('id')->get();
        if ($sections->isEmpty() && ! HomepageSection::exists()) {
            $sections = collect(self::defaults())->map(fn (array $data, int $i): HomepageSection => new HomepageSection($data + ['active' => true, 'sort_order' => $i]));
        }
        $max = max(1, min(24, (int) $sections->filter(fn ($s) => $s->type->value === 'featured_products')->max(fn ($s) => (int) ($s->settings['max_items'] ?? 6))));

        return [
            'sections' => $sections,
            'banners' => Banner::where('active', true)->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))->orderBy('sort_order')->orderBy('id')->get(),
            'products' => $catalog->base($categories->all()->keys()->all())->where('featured', true)->orderByDesc('id')->limit($max)->get(),
            'featuredCategories' => $categories->all()->where('featured', true),
        ];
    }
}
