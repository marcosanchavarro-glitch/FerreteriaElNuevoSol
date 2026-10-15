<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/** Monetary values are non-negative integer centavos, never decimal floats. */
class MinorUnits implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        return $value === null ? null : (int) $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value === null) {
            return null;
        }

        if ((! is_int($value) && ! is_string($value)) || ! preg_match('/^\d{1,15}$/D', (string) $value)) {
            throw new InvalidArgumentException("{$key} must be integer minor units (0..999999999999999).");
        }

        return (int) $value;
    }
}
