<?php

namespace App\Support;

class ActivityDisplay
{
    public static function field(string $field): string
    {
        return ['sku' => 'SKU', 'name' => 'Nombre', 'slug' => 'Enlace', 'short_description' => 'Descripción breve', 'description' => 'Descripción', 'product_type' => 'Tipo de producto', 'sale_unit' => 'Unidad de venta', 'purchase_mode' => 'Modo de compra', 'price' => 'Precio', 'stock' => 'Stock', 'status' => 'Estado', 'featured' => 'Destacado', 'category_ids' => 'Categorías (IDs)', 'product_id' => 'Producto', 'value' => 'Presentación', 'active' => 'Activo', 'parent_id' => 'Categoría padre', 'image_path' => 'Referencia de imagen', 'sort_order' => 'Orden', 'images' => 'Galería', 'type' => 'Tipo de sección', 'title' => 'Título', 'subtitle' => 'Subtítulo', 'content' => 'Texto', 'button_text' => 'Texto del botón', 'button_url' => 'Enlace del botón', 'max_items' => 'Cantidad máxima', 'order' => 'Orden de entidades (IDs)', 'business_name' => 'Nombre comercial', 'phone' => 'Teléfono comercial', 'whatsapp_number' => 'WhatsApp', 'pickup_address' => 'Dirección de retiro', 'pickup_hours' => 'Horarios', 'id' => 'ID', 'path' => 'Referencia', 'alt_text' => 'Texto alternativo', 'is_primary' => 'Principal'][$field] ?? $field;
    }

    public static function value(mixed $value, string $field = ''): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        if (is_bool($value)) {
            return $value ? 'Sí' : 'No';
        }
        if (is_array($value)) {
            if ($field === 'images') {
                return implode("\n\n", array_map(fn ($row) => implode("\n", array_map(fn ($key) => self::field($key).': '.self::value($row[$key], $key), array_keys($row))), $value)) ?: 'Sin imágenes';
            }

            return implode(', ', array_map(fn ($v) => self::value($v), $value)) ?: '—';
        }
        if ($field === 'price') {
            return ProductDisplay::money($value);
        }

        return match ($field) {
            'status' => ['draft' => 'Borrador', 'published' => 'Publicado', 'private' => 'Privado', 'inactive' => 'Inactivo', 'received' => 'Recibido', 'preparing' => 'En preparación', 'ready_for_pickup' => 'Listo para retirar', 'picked_up' => 'Retirado', 'cancelled' => 'Cancelado'][(string) $value] ?? (string) $value,
            'product_type' => ['simple' => 'Simple', 'variable' => 'Variable'][(string) $value] ?? (string) $value,
            default => (string) $value,
        };
    }
}
