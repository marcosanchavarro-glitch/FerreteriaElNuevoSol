<?php

namespace App\Enums;

enum HomepageSectionType: string
{
    case Hero = 'hero';
    case FeaturedCategories = 'featured_categories';
    case FeaturedProducts = 'featured_products';
    case Banner = 'banner';
    case Promotion = 'promotion';
    case TextImage = 'text_image';
    case Text = 'text';
    case Image = 'image';

    public function label(): string
    {
        return match ($this) {
            self::Hero => 'Portada principal (hero)',
            self::FeaturedCategories => 'Categorías destacadas',
            self::FeaturedProducts => 'Productos destacados',
            self::Banner => 'Grupo de banners',
            self::Promotion => 'Promoción',
            self::TextImage => 'Texto e imagen',
            self::Text => 'Texto',
            self::Image => 'Imagen',
        };
    }
}
