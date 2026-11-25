<?php

namespace App\Services\Content;

use App\Models\Setting;

class SiteSettings
{
    public const MAPS_EMBED_PATTERN = '#^https://www\.google\.com/maps/embed\?pb=[A-Za-z0-9!%._~+,;:/=-]+$#D';

    public const FIELDS = ['business_name' => 'Nombre comercial', 'phone' => 'Teléfono', 'contact_primary_number' => 'Número primario', 'contact_secondary_number' => 'Número secundario', 'contact_tertiary_number' => 'Número terciario', 'whatsapp_orders_contact' => 'Número elegido para pedidos', 'whatsapp_number' => 'Otro WhatsApp para pedidos', 'whatsapp_number_kind' => 'Tipo de número para pedidos', 'whatsapp_inquiries_contact' => 'Número elegido para consultas', 'whatsapp_inquiries_number' => 'Otro WhatsApp para consultas', 'whatsapp_inquiries_kind' => 'Tipo de número para consultas', 'pickup_address' => 'Dirección de retiro', 'locality' => 'Localidad', 'province' => 'Provincia', 'postal_code' => 'Código postal', 'maps_embed_url' => 'Mapa integrado (opcional)', 'maps_url' => 'Enlace de Google Maps', 'pickup_hours' => 'Horarios'];

    public function all(): array
    {
        $defaults = array_fill_keys(array_keys(self::FIELDS), '');
        $defaults = array_replace($defaults, ['business_name' => 'El Nuevo Sol', 'whatsapp_orders_contact' => 'custom', 'whatsapp_inquiries_contact' => 'custom', 'whatsapp_number_kind' => 'pending', 'whatsapp_inquiries_kind' => 'pending', 'whatsapp_number' => config('shop.whatsapp_number', ''), 'pickup_address' => config('shop.pickup_address', ''), 'pickup_hours' => config('shop.pickup_hours', '')]);

        return array_replace($defaults, Setting::whereIn('key', array_keys(self::FIELDS))->pluck('value', 'key')->map(fn ($v): string => (string) $v)->all());
    }

    public function get(string $key): string
    {
        if ($key === 'whatsapp_number') {
            return $this->contactNumber('orders', $this->all());
        }

        return (string) ($this->all()[$key] ?? '');
    }

    public function contactNumber(string $purpose, array $values): string
    {
        $choice = $values[$purpose === 'orders' ? 'whatsapp_orders_contact' : 'whatsapp_inquiries_contact'] ?? 'custom';
        if (in_array($choice, ['primary', 'secondary', 'tertiary'], true)) {
            return (string) ($values['contact_'.$choice.'_number'] ?? '');
        }

        return (string) ($values[$purpose === 'orders' ? 'whatsapp_number' : 'whatsapp_inquiries_number'] ?? '');
    }

    public function inquiryUrl(?array $values = null): ?string
    {
        $number = $this->contactNumber('inquiries', $values ?? $this->all());
        if (config('demo.enabled') || ! preg_match('/^[1-9][0-9]{7,14}$/D', $number)) {
            return null;
        }

        return 'https://wa.me/'.$number.'?text='.rawurlencode('Hola, quiero hacer una consulta sobre el catálogo.');
    }

    public function mapEmbedUrl(array $values): ?string
    {
        foreach (['pickup_address', 'locality', 'province', 'maps_url'] as $field) {
            if (trim($values[$field] ?? '') === '') {
                return null;
            }
        }
        $url = $values['maps_embed_url'] ?? '';

        return preg_match(self::MAPS_EMBED_PATTERN, $url) ? $url : null;
    }
}
