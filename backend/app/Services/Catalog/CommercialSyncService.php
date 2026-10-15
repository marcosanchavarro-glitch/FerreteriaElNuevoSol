<?php

namespace App\Services\Catalog;

use App\Casts\ScaledQuantity;
use App\Enums\ProductType;
use App\Enums\SaleUnit;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SyncRun;
use App\Services\Audit\ActivityLogService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CommercialSyncService
{
    public const SOURCE = 'normalized_json_test';

    public function normalizeSku(mixed $sku): ?string
    {
        if (! is_string($sku)) {
            return null;
        }
        $sku = trim($sku, ' ');

        return preg_match('/^[A-Za-z0-9][A-Za-z0-9._\/-]{0,254}$/D', $sku) ? $sku : null;
    }

    public function decode(string $json): array
    {
        if (strlen($json) > 256 * 1024 || ! str_starts_with(ltrim($json), '[')) {
            throw ValidationException::withMessages(['file' => 'Se requiere una lista JSON de hasta 256 KB.']);
        }
        try {
            $records = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw ValidationException::withMessages(['file' => 'JSON inválido.']);
        }
        if (! is_array($records) || ! array_is_list($records) || count($records) < 1 || count($records) > 100) {
            throw ValidationException::withMessages(['file' => 'Se requiere una lista de 1 a 100 registros.']);
        }

        return $records;
    }

    private function validRecord(mixed $record): bool
    {
        if (! is_array($record) || array_diff(array_keys($record), ['sku', 'kind', 'price_cents', 'stock', 'name', 'sale_unit', 'parent_sku']) !== [] || $this->normalizeSku($record['sku'] ?? null) === null) {
            return false;
        }
        if (! in_array($record['kind'] ?? null, ['product', 'variant'], true) || (! array_key_exists('price_cents', $record) && ! array_key_exists('stock', $record))) {
            return false;
        }
        if (array_key_exists('price_cents', $record) && (! is_int($record['price_cents']) || $record['price_cents'] < 0 || $record['price_cents'] > 999999999999999)) {
            return false;
        }
        if (array_key_exists('stock', $record) && (! is_string($record['stock']) || ! preg_match('/^\d{1,12}(?:\.\d{1,3})?$/D', $record['stock']))) {
            return false;
        }
        if (array_key_exists('name', $record) && (! is_string($record['name']) || trim($record['name']) === '' || mb_strlen($record['name']) > 255 || preg_match('/[\x00-\x1f]/', $record['name']))) {
            return false;
        }
        if (array_key_exists('sale_unit', $record) && (! is_string($record['sale_unit']) || SaleUnit::tryFrom($record['sale_unit']) === null)) {
            return false;
        }

        return ! array_key_exists('parent_sku', $record) || ($record['kind'] === 'variant' && $this->normalizeSku($record['parent_sku']) !== null);
    }

    private function plan(array $records, bool $lock = false): array
    {
        $index = [];
        foreach ([Product::class, ProductVariant::class] as $class) {
            foreach ($class::query()->when($lock, fn ($query) => $query->lockForUpdate())->orderBy('id')->get() as $model) {
                if (($sku = $this->normalizeSku($model->sku)) !== null) {
                    $index['sku:'.$sku][] = $model;
                }
            }
        }
        $counts = [];
        foreach ($records as $record) {
            if (is_array($record) && ($sku = $this->normalizeSku($record['sku'] ?? null)) !== null) {
                $key = 'sku:'.$sku;
                $counts[$key] = ($counts[$key] ?? 0) + 1;
            }
        }
        $rows = [];
        foreach ($records as $offset => $record) {
            $sku = is_array($record) ? $this->normalizeSku($record['sku'] ?? null) : null;
            $row = ['line' => $offset + 1, 'sku' => $sku, 'action' => 'invalid', 'reason' => 'Registro inválido o campo no permitido.'];
            if (! $this->validRecord($record)) {
                $rows[] = $row;

                continue;
            }
            $matches = $index['sku:'.$sku] ?? [];
            $row += ['kind' => $record['kind'], 'id' => null, 'parent_id' => null, 'before' => [], 'after' => [], 'version' => null];
            $row['action'] = 'conflict';
            $row['reason'] = 'SKU duplicado en el archivo o ambiguo en el catálogo.';
            if ($counts['sku:'.$sku] > 1 || count($matches) > 1) {
                $rows[] = $row;

                continue;
            }
            $target = $matches[0] ?? null;
            $parent = null;
            if (isset($record['parent_sku'])) {
                $parents = $index['sku:'.$this->normalizeSku($record['parent_sku'])] ?? [];
                $parent = count($parents) === 1 && $parents[0] instanceof Product && $parents[0]->product_type === ProductType::Variable ? $parents[0] : null;
                if (! $parent || ($target instanceof ProductVariant && $target->product_id !== $parent->id)) {
                    $row['reason'] = 'Padre variable inexistente, ambiguo o distinto del actual.';
                    $rows[] = $row;

                    continue;
                }
            }
            if (($target && (($record['kind'] === 'product') !== ($target instanceof Product))) || ($target instanceof Product && $target->product_type === ProductType::Variable)) {
                $row['reason'] = 'Tipo incompatible: los precios del padre variable pertenecen a sus variantes.';
                $rows[] = $row;

                continue;
            }
            if (! $target && (! isset($record['name']) || ($record['kind'] === 'product' && ! isset($record['sale_unit'])) || ($record['kind'] === 'variant' && ! $parent))) {
                $row['reason'] = 'Alta sin nombre/unidad explícita o sin padre variable existente.';
                $rows[] = $row;

                continue;
            }
            if ($target && isset($record['sale_unit']) && $record['sale_unit'] !== ($target instanceof ProductVariant ? $target->effectiveSaleUnit()->value : $target->sale_unit->value)) {
                $row['reason'] = 'La unidad suministrada no coincide; no se realizan conversiones.';
                $rows[] = $row;

                continue;
            }
            $row['id'] = $target?->id;
            $row['parent_id'] = $parent?->id;
            $row['version'] = hash('sha256', json_encode([$target?->getAttributes(), $parent?->getAttributes()], JSON_THROW_ON_ERROR));
            foreach (['price_cents' => 'price', 'stock' => 'stock'] as $input => $field) {
                if (array_key_exists($input, $record)) {
                    $value = $record[$input];
                    if ($field === 'stock') {
                        $cast = new ScaledQuantity;
                        $value = $cast->get(new Product, 'stock', $cast->set(new Product, 'stock', $value, []), []);
                    }
                    $row['before'][$field] = $target?->getAttribute($field);
                    $row['after'][$field] = $value;
                }
            }
            $row['action'] = ! $target ? 'create' : (($row['before'] !== $row['after'] || $target->external_synced_at === null) ? 'update' : 'unchanged');
            $row['reason'] = $target ? 'Solo precio y stock suministrados; se marca gestión externa.' : 'Alta sin publicación automática.';
            $rows[] = $row;
        }

        return $rows;
    }

    public function preview(array $records): SyncRun
    {
        Gate::authorize('manage-commercial-sync');
        $records = $this->decode(json_encode($records, JSON_THROW_ON_ERROR));
        // Invalid records retain only position and valid SKU, so duplicates remain detectable.
        $safeRecords = array_map(fn ($record) => $this->validRecord($record) ? $record : ['sku' => is_array($record) ? $this->normalizeSku($record['sku'] ?? null) : null], $records);
        $rows = $this->plan($safeRecords);

        return SyncRun::create([
            'source' => self::SOURCE, 'started_at' => now(), 'finished_at' => now(), 'status' => 'completed',
            'processed_count' => count($records), 'error_count' => count(array_filter($rows, fn ($row) => in_array($row['action'], ['invalid', 'conflict'], true))),
            'error_details' => ['mode' => 'preview', 'actor_id' => auth()->id(), 'expires_at' => now()->addMinutes(30)->timestamp, 'records' => $safeRecords, 'rows' => $rows, 'fingerprint' => hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR))],
        ]);
    }

    public function apply(SyncRun $preview): SyncRun
    {
        Gate::authorize('manage-commercial-sync');

        return Cache::lock('commercial-sync', 120)->block(5, function () use ($preview): SyncRun {
            try {
                return DB::transaction(function () use ($preview): SyncRun {
                    $preview = SyncRun::whereKey($preview->id)->lockForUpdate()->firstOrFail();
                    $data = $preview->error_details;
                    if ($preview->source !== self::SOURCE || ($data['mode'] ?? '') !== 'preview' || ($data['actor_id'] ?? null) !== auth()->id() || ($data['expires_at'] ?? 0) < now()->timestamp || isset($data['applied_run_id'])) {
                        throw ValidationException::withMessages(['preview' => 'La vista previa venció, pertenece a otro usuario o ya fue utilizada.']);
                    }
                    $rows = $this->plan($data['records'], true);
                    if (! hash_equals($data['fingerprint'], hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR)))) {
                        throw ValidationException::withMessages(['preview' => 'El catálogo cambió. Generá otra vista previa antes de confirmar.']);
                    }
                    $run = SyncRun::create(['source' => self::SOURCE, 'started_at' => now(), 'status' => 'running']);
                    $created = $updated = 0;
                    $audit = app(ActivityLogService::class);
                    foreach ($rows as $i => $row) {
                        if (! in_array($row['action'], ['create', 'update'], true)) {
                            continue;
                        }
                        $class = $row['kind'] === 'product' ? Product::class : ProductVariant::class;
                        $target = $row['id'] ? $class::findOrFail($row['id']) : new $class;
                        $before = $audit->snapshot($target);
                        if (! $target->exists) {
                            $record = $data['records'][$i];
                            $target->fill(['sku' => $row['sku'], 'name' => $record['name'], 'sale_unit' => $record['sale_unit'] ?? null, 'purchase_mode' => 'inquiry']);
                            $target->fill($row['kind'] === 'product'
                                ? ['slug' => 'import-'.Str::uuid(), 'product_type' => 'simple', 'status' => 'draft', 'featured' => false]
                                : ['product_id' => $row['parent_id'], 'active' => false]);
                            $created++;
                        } else {
                            $updated++;
                        }
                        $target->fill($row['after']);
                        $target->external_synced_at = now();
                        $target->save();
                        $audit->record($audit->module($target), $row['action'] === 'create' ? 'created' : 'updated', $target, $before, $audit->snapshot($target), auth()->user(), 'external');
                    }
                    $errors = count(array_filter($rows, fn ($row) => in_array($row['action'], ['invalid', 'conflict'], true)));
                    $skipped = count($rows) - $created - $updated;
                    $run->update(['finished_at' => now(), 'status' => 'completed', 'processed_count' => count($rows), 'created_count' => $created, 'updated_count' => $updated, 'error_count' => $errors, 'error_details' => ['mode' => 'apply', 'preview_id' => $preview->id, 'skipped_count' => $skipped, 'rows' => $rows]]);
                    $audit->record('sync', 'updated', $run, [], ['created_count' => $created, 'updated_count' => $updated, 'skipped_count' => $skipped, 'error_count' => $errors], auth()->user(), 'external');
                    $preview->update(['error_details' => [...$data, 'applied_run_id' => $run->id]]);

                    return $run;
                });
            } catch (ValidationException $exception) {
                throw $exception;
            } catch (\Throwable) {
                return SyncRun::create(['source' => self::SOURCE, 'started_at' => now(), 'finished_at' => now(), 'status' => 'failed', 'error_count' => 1, 'error_details' => ['mode' => 'apply', 'preview_id' => $preview->id, 'message' => 'No se aplicó ningún cambio: operación revertida. Generá otra vista previa.']]);
            }
        });
    }
}
