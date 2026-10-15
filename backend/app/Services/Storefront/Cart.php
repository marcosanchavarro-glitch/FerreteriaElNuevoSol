<?php

namespace App\Services\Storefront;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\PurchaseMode;
use App\Enums\SaleUnit;
use App\Models\Product;
use Illuminate\Contracts\Session\Session;
use Illuminate\Validation\ValidationException;

class Cart
{
    private const int MAX_AMOUNT = 999999999999999;

    public function __construct(private Session $session, private PublicImages $images) {}

    public static function fractional(SaleUnit $unit): bool
    {
        return in_array($unit, [SaleUnit::Meter, SaleUnit::Kilogram, SaleUnit::Gram, SaleUnit::Liter, SaleUnit::Milliliter], true);
    }

    public static function quantity(mixed $value): int
    {
        if ((! is_string($value) && ! is_int($value)) || ! preg_match('/^\d{1,4}(?:[.,]\d{1,3})?$/D', (string) $value)) {
            throw ValidationException::withMessages(['quantity' => 'Ingresá una cantidad válida, hasta 9.999,999 y con un máximo de tres decimales.']);
        }
        $parts = explode('.', str_replace(',', '.', (string) $value));
        $scaled = (int) $parts[0] * 1000 + (int) str_pad($parts[1] ?? '', 3, '0');
        if ($scaled < 1) {
            throw ValidationException::withMessages(['quantity' => 'La cantidad debe ser mayor que cero.']);
        }

        return $scaled;
    }

    public static function decimal(int $scaled): string
    {
        return intdiv($scaled, 1000).'.'.str_pad((string) ($scaled % 1000), 3, '0', STR_PAD_LEFT);
    }

    public function lines(): array
    {
        return $this->session->get('cart', []);
    }

    public function resolve(array $line): array
    {
        $product = Product::with(['variants', 'images' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order')])->find($line['product_id']);
        if (! $product || $product->status !== ProductStatus::Published) {
            throw ValidationException::withMessages(['cart' => 'Un producto del carrito ya no está disponible. Eliminá esa línea para continuar.']);
        }
        $variant = null;
        if ($product->product_type === ProductType::Variable) {
            $variant = $product->variants->firstWhere('id', $line['product_variant_id']);
            if (! $variant || ! $variant->active) {
                throw ValidationException::withMessages(['cart' => $product->name.': elegí una presentación activa y válida.']);
            }
            $variant->setRelation('product', $product);
        } elseif ($line['product_variant_id'] !== null) {
            throw ValidationException::withMessages(['cart' => $product->name.': este producto no admite variantes.']);
        }
        $unit = $variant ? $variant->effectiveSaleUnit() : $product->sale_unit;
        $mode = $variant ? $variant->effectivePurchaseMode() : $product->purchase_mode;
        $price = $variant ? $variant->price : $product->price;
        $stock = $variant ? $variant->stock : $product->stock;
        $quantity = self::quantity($line['quantity']);
        $label = $product->name.($variant ? ' · '.$variant->name : '');
        if (! self::fractional($unit) && $quantity % 1000 !== 0) {
            throw ValidationException::withMessages(['quantity' => $label.': esta unidad requiere cantidades enteras.']);
        }
        if ($mode === PurchaseMode::Calculated && $stock !== null && $quantity > (int) str_replace('.', '', $stock)) {
            throw ValidationException::withMessages(['cart' => $label.': no hay stock suficiente. Corregí la cantidad o eliminá el producto.']);
        }
        $confirm = $mode === PurchaseMode::Inquiry || $price === null;
        $subtotal = null;
        if (! $confirm) {
            if ($price < 0 || $price > intdiv(PHP_INT_MAX - 500, $quantity)) {
                throw ValidationException::withMessages(['cart' => $label.': el importe excede el límite del pedido. Consultá al local.']);
            }
            $subtotal = intdiv($price * $quantity + 500, 1000);
            if ($subtotal > self::MAX_AMOUNT) {
                throw ValidationException::withMessages(['cart' => $label.': el importe excede el límite del pedido. Consultá al local.']);
            }
        }

        return ['product_id' => $product->id, 'product_variant_id' => $variant?->id,
            'product_name' => $product->name, 'variant_name' => $variant?->name,
            'sku' => $variant ? $variant->sku : $product->sku, 'sale_unit' => $unit,
            'quantity' => self::decimal($quantity), 'unit_price' => $confirm ? null : $price,
            'subtotal' => $subtotal, 'requires_confirmation' => $confirm,
            'image' => $this->images->product($product->images->first()), 'slug' => $product->slug];
    }

    public function summary(bool $strict = true): array
    {
        $items = [];
        $total = 0;
        $pending = 0;
        foreach ($this->lines() as $key => $line) {
            try {
                $item = $this->resolve($line);
                if (($item['subtotal'] ?? 0) > self::MAX_AMOUNT - $total) {
                    throw ValidationException::withMessages(['cart' => 'El total excede el límite admitido. Dividí el pedido.']);
                }
                $total += $item['subtotal'] ?? 0;
                $pending += (int) $item['requires_confirmation'];
                $items[$key] = $item;
            } catch (ValidationException $exception) {
                if ($strict) {
                    throw $exception;
                }
                $items[$key] = $line + ['error' => collect($exception->errors())->flatten()->first()];
            }
        }

        return ['items' => $items, 'total' => $total, 'pending' => $pending];
    }

    public function put(int $productId, ?int $variantId, mixed $quantity, bool $accumulate): void
    {
        $lines = $this->lines();
        $key = $productId.'-'.($variantId ?? 0);
        $scaled = self::quantity($quantity);
        if ($accumulate && isset($lines[$key])) {
            $scaled += self::quantity($lines[$key]['quantity']);
        }
        $line = ['product_id' => $productId, 'product_variant_id' => $variantId, 'quantity' => self::decimal($scaled)];
        $this->resolve($line);
        if (! isset($lines[$key]) && count($lines) >= 50) {
            throw ValidationException::withMessages(['cart' => 'El carrito admite hasta 50 productos distintos.']);
        }
        $lines[$key] = $line;
        $this->session->put('cart', $lines);
    }

    public function remove(string $key): void
    {
        $lines = $this->lines();
        unset($lines[$key]);
        $this->session->put('cart', $lines);
    }
}
