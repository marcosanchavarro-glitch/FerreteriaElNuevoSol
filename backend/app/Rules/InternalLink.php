<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class InternalLink implements ValidationRule
{
    public static function safe(?string $value): bool
    {
        if (! $value) {
            return false;
        }
        $decoded = rawurldecode($value);

        return str_starts_with($decoded, '/') && ! str_starts_with($decoded, '//')
            && preg_match('/[\x00-\x20\x7f\\\\<>"\x27]/u', $decoded) === 0
            && ! str_contains($decoded, '%');
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! self::safe($value)) {
            $fail('Usá una ruta interna, por ejemplo /productos. No se admiten enlaces externos.');
        }
    }
}
