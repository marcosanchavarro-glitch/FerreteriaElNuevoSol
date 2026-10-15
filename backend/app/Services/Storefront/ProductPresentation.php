<?php

namespace App\Services\Storefront;

use App\Enums\ProductType;
use App\Enums\PurchaseMode;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\ProductDisplay;

class ProductPresentation
{
    public function __construct(private PublicImages $images) {}

    private function availability(?string $stock): string
    {
        if ($stock === null) {
            return 'Consultar disponibilidad';
        }

        return str_starts_with($stock, '-') || trim(str_replace('.', '', $stock), '0') === '' ? 'Sin stock' : 'Disponible';
    }

    /** Explicit public projection: never serialize Eloquent models or numeric stock. */
    public function offer(Product $product, ?ProductVariant $variant = null): array
    {
        if ($variant) {
            $variant->setRelation('product', $product);
        }
        $mode = $variant ? $variant->effectivePurchaseMode() : $product->purchase_mode;
        $price = $variant ? $variant->price : $product->price;
        $stock = $variant ? $variant->stock : $product->stock;

        return ['step' => Cart::fractional($variant ? $variant->effectiveSaleUnit() : $product->sale_unit) ? '1' : '1000', 'can_add' => $mode === PurchaseMode::Inquiry || $this->availability($stock) !== 'Sin stock', 'id' => $variant?->id, 'name' => $variant?->name ?? $product->name, 'sku' => $variant ? $variant->sku : $product->sku, 'price' => $mode === PurchaseMode::Calculated && $price !== null ? ProductDisplay::money($price) : 'Precio a confirmar', 'unit' => ProductDisplay::label($variant ? $variant->effectiveSaleUnit() : $product->sale_unit), 'availability' => $this->availability($stock), 'mode' => ProductDisplay::label($mode)];
    }

    public function card(Product $product): array
    {
        $offer = $this->offer($product);
        $mixed = false;
        if ($product->product_type === ProductType::Variable) {
            $offers = $product->variants->map(fn (ProductVariant $v): array => $this->offer($product, $v));
            $prices = $product->variants->filter(fn (ProductVariant $v): bool => $v->effectivePurchaseMode() === PurchaseMode::Calculated && $v->price !== null)->pluck('price');
            $offer['price'] = $prices->isNotEmpty() ? 'Desde '.ProductDisplay::money($prices->min()) : 'Precio a confirmar';
            $offer['unit'] = 'Según presentación';
            $offer['availability'] = $offers->isEmpty() ? 'Sin presentaciones disponibles' : ($offers->contains('availability', 'Disponible') ? 'Disponible' : ($offers->contains('availability', 'Consultar disponibilidad') ? 'Consultar disponibilidad' : 'Sin stock'));
            $mixed = $prices->count() !== $offers->count() && $prices->isNotEmpty();
        }

        return ['product' => $product, 'offer' => $offer, 'image' => $this->images->product($product->images->first()), 'alt' => $product->images->first()?->alt_text ?? '', 'mixed' => $mixed];
    }
}
