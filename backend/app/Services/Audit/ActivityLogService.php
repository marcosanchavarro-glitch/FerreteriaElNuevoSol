<?php

namespace App\Services\Audit;

use App\Models\ActivityLog;
use App\Models\Banner;
use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ActivityLogService
{
    public const MODULES = ['products' => 'Productos', 'variants' => 'Variantes', 'categories' => 'Categorías', 'images' => 'Imágenes', 'orders' => 'Pedidos', 'sections' => 'Página de inicio', 'banners' => 'Banners', 'settings' => 'Configuración'];

    public const ACTIONS = ['created' => 'Creación', 'updated' => 'Modificación', 'categories' => 'Categorías asignadas', 'uploaded' => 'Imagen subida', 'deleted' => 'Imagen eliminada', 'primary' => 'Imagen principal', 'reordered' => 'Orden actualizado', 'status' => 'Cambio de estado', 'visibility' => 'Visibilidad', 'image' => 'Cambio de imagen', 'denied' => 'Operación rechazada'];

    public const FIELDS = [
        'products' => ['sku', 'name', 'slug', 'short_description', 'description', 'product_type', 'sale_unit', 'purchase_mode', 'price', 'stock', 'status', 'featured', 'category_ids'],
        'variants' => ['product_id', 'sku', 'name', 'value', 'sale_unit', 'purchase_mode', 'price', 'stock', 'active'],
        'categories' => ['parent_id', 'name', 'slug', 'description', 'image_path', 'active', 'featured', 'sort_order'],
        'images' => ['images'],
        'orders' => ['status'],
        'sections' => ['type', 'title', 'subtitle', 'content', 'image_path', 'button_text', 'button_url', 'max_items', 'active', 'sort_order', 'order'],
        'banners' => ['title', 'subtitle', 'image_path', 'button_text', 'button_url', 'active', 'sort_order', 'order'],
        'settings' => ['business_name', 'phone', 'whatsapp_number', 'pickup_address', 'pickup_hours'],
    ];

    public function module(Model $subject): string
    {
        return match (true) {
            $subject instanceof Product => 'products', $subject instanceof ProductVariant => 'variants',
            $subject instanceof Category => 'categories', $subject instanceof ProductImage => 'images',
            $subject instanceof HomepageSection => 'sections', $subject instanceof Banner => 'banners',
            $subject instanceof Order => 'orders', default => throw new InvalidArgumentException('Unsupported audit subject.'),
        };
    }

    public function snapshot(Model $subject): array
    {
        if (! $subject->exists) {
            return [];
        }
        $data = [];
        foreach (self::FIELDS[$this->module($subject)] as $field) {
            if (in_array($field, ['category_ids', 'order', 'images'], true)) {
                continue;
            }
            $value = $field === 'max_items' ? ($subject->settings['max_items'] ?? null) : $subject->getAttribute($field);
            $data[$field] = $value instanceof \BackedEnum ? $value->value : $value;
        }

        return $data;
    }

    public function gallery(Product $product): array
    {
        return ['images' => $product->images()->orderBy('id')->get(['id', 'path', 'alt_text', 'is_primary', 'sort_order'])->map(fn ($image) => $image->only(['id', 'path', 'alt_text', 'is_primary', 'sort_order']))->all()];
    }

    public function order(Model $subject): array
    {
        return ['order' => $subject->newQuery()->orderBy('sort_order')->orderBy('id')->pluck('id')->all()];
    }

    /** Explicit allowlists apply again at the write boundary, even for trusted callers. */
    public function sanitize(string $module, array $values): array
    {
        $safe = [];
        foreach (self::FIELDS[$module] ?? [] as $field) {
            if (! array_key_exists($field, $values)) {
                continue;
            }
            $value = $values[$field];
            if ($field === 'images' && is_array($value)) {
                $safe[$field] = array_values(array_map(function ($row): array {
                    return array_filter(array_intersect_key(is_array($row) ? $row : [], array_flip(['id', 'path', 'alt_text', 'is_primary', 'sort_order'])), fn ($v) => is_scalar($v) || $v === null);
                }, $value));
            } elseif (in_array($field, ['category_ids', 'order'], true) && is_array($value)) {
                $safe[$field] = array_values(array_map('intval', array_filter($value, fn ($v) => is_int($v) || (is_string($v) && ctype_digit($v)))));
            } elseif (is_scalar($value) || $value === null) {
                $safe[$field] = $value;
            }
        }

        return $safe;
    }

    public function record(string $module, string $action, Model|int|string|null $subject, array $before, array $after, ?User $actor = null, string $origin = 'admin', string $result = 'success'): ?ActivityLog
    {
        if (! isset(self::MODULES[$module], self::ACTIONS[$action]) || ! in_array($origin, ['admin', 'system', 'external'], true) || ! in_array($result, ['success', 'rejected'], true)) {
            throw new InvalidArgumentException('Unsupported audit event.');
        }
        if ($result === 'success' && DB::transactionLevel() === 0) {
            throw new \LogicException('Successful audit events require the operation transaction.');
        }
        $before = $this->sanitize($module, $before);
        $after = $this->sanitize($module, $after);
        $keys = array_filter(array_unique([...array_keys($before), ...array_keys($after)]), fn ($key) => ! array_key_exists($key, $before) || ! array_key_exists($key, $after) || $before[$key] !== $after[$key]);
        if ($result === 'success' && $keys === []) {
            return null;
        }
        $actor ??= $origin === 'admin' ? auth()->user() : null;
        if ($origin === 'admin' && $actor === null) {
            $origin = 'system';
        }
        $log = new ActivityLog;
        $log->forceFill([
            'user_id' => $actor?->id, 'actor_id' => $actor?->id,
            'actor_name' => $actor ? trim($actor->first_name.' '.$actor->last_name) : 'Sistema', 'actor_role' => $actor?->role->value,
            'action' => $action, 'module' => $module, 'entity_type' => $module, 'entity_id' => $subject instanceof Model ? (string) $subject->getKey() : ($subject === null ? null : (string) $subject),
            'origin' => $origin, 'result' => $result,
            'before_values' => array_intersect_key($before, array_flip($keys)), 'after_values' => array_intersect_key($after, array_flip($keys)), 'created_at' => now(),
        ])->save();

        return $log;
    }

    public function changed(Model $subject, array $before, string $action = 'updated', ?User $actor = null): void
    {
        $this->record($this->module($subject), $action, $subject, $before, $this->snapshot($subject), $actor);
    }
}
