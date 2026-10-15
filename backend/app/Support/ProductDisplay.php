<?php

namespace App\Support;

use BackedEnum;

class ProductDisplay
{
    public static function quantity(string $quantity): string
    {
        return str_replace('.', ',', str_contains($quantity, '.') ? rtrim(rtrim($quantity, '0'), '.') : $quantity);
    }

    public static function money(?int $centavos): string
    {
        if ($centavos === null) {
            return 'A confirmar';
        }
        $whole = (string) intdiv($centavos, 100);

        return '$ '.preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $whole).','.str_pad((string) ($centavos % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function moneyInput(?int $centavos): string
    {
        return $centavos === null ? '' : intdiv($centavos, 100).'.'.str_pad((string) ($centavos % 100), 2, '0', STR_PAD_LEFT);
    }

    /** Accept only a previously validated decimal string. */
    public static function toMinorUnits(string $input): int
    {
        $parts = explode('.', str_replace(',', '.', $input));

        return (int) $parts[0] * 100 + (int) str_pad($parts[1] ?? '', 2, '0');
    }

    public static function label(BackedEnum $value): string
    {
        return match ($value->value) {
            'unit' => 'Unidad','meter' => 'Metro','kilogram' => 'Kilogramo','gram' => 'Gramo','liter' => 'Litro','milliliter' => 'Mililitro','box' => 'Caja','package' => 'Paquete','bundle' => 'Bulto','roll' => 'Rollo','other' => 'Otra',
            'draft' => 'Borrador','published' => 'Publicado','private' => 'Privado / inactivo','simple' => 'Simple','variable' => 'Con variantes','calculated' => 'Compra calculable','inquiry' => 'Requiere confirmación',
            default => $value->value,
        };
    }
}
