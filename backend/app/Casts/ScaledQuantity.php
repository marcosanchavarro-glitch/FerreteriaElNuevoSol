<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/** Decimal strings in PHP; signed integer thousandths in the database. */
class ScaledQuantity implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        $integer = (int) $value;
        $absolute = abs($integer);

        return ($integer < 0 ? '-' : '').intdiv($absolute, 1000).'.'.str_pad((string) ($absolute % 1000), 3, '0', STR_PAD_LEFT);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value === null) {
            return null;
        }

        if ((! is_int($value) && ! is_string($value)) || ! preg_match('/^(-?)(\d{1,12})(?:\.(\d{1,3}))?$/D', (string) $value, $parts)) {
            throw new InvalidArgumentException("{$key} requires a decimal string with up to 12 integer and 3 fractional digits; floats are rejected.");
        }

        $scaled = (int) $parts[2] * 1000 + (int) str_pad($parts[3] ?? '', 3, '0');

        return $parts[1] === '-' ? -$scaled : $scaled;
    }
}
