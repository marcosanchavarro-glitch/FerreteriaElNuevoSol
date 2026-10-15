<?php

namespace App\Services\Content;

use App\Models\Setting;

class SiteSettings
{
    public const FIELDS = ['business_name' => 'Nombre comercial', 'phone' => 'Teléfono', 'whatsapp_number' => 'WhatsApp', 'pickup_address' => 'Dirección de retiro', 'pickup_hours' => 'Horarios'];

    public function all(): array
    {
        $defaults = ['business_name' => 'El Nuevo Sol', 'phone' => '', 'whatsapp_number' => config('shop.whatsapp_number', ''), 'pickup_address' => config('shop.pickup_address', ''), 'pickup_hours' => config('shop.pickup_hours', '')];

        return array_replace($defaults, Setting::whereIn('key', array_keys(self::FIELDS))->pluck('value', 'key')->map(fn ($v): string => (string) $v)->all());
    }

    public function get(string $key): string
    {
        return (string) ($this->all()[$key] ?? '');
    }
}
