<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\SyncRun;
use App\Models\User;
use App\Services\Audit\ActivityLogService;
use App\Services\Catalog\CommercialSyncService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CommercialSyncTest extends TestCase
{
    use RefreshDatabase;

    private function service(): CommercialSyncService
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        return app(CommercialSyncService::class);
    }

    public function test_preview_and_apply_preserve_editorial_data_orders_and_exact_values(): void
    {
        $service = $this->service();
        $product = Product::factory()->create(['sku' => '00123', 'description' => 'Texto web', 'featured' => true, 'status' => 'published']);
        $image = ProductImage::factory()->create(['product_id' => $product->id]);
        $category = Category::factory()->create();
        $product->categories()->attach($category);
        $item = OrderItem::factory()->create(['product_id' => $product->id]);
        $product->refresh();
        $image->refresh();
        $item->refresh();
        $before = $product->getAttributes();
        $preview = $service->preview([['sku' => ' 00123 ', 'kind' => 'product', 'price_cents' => 10001, 'stock' => '1.125', 'name' => 'Nombre externo ignorado']]);
        $this->assertSame($before, $product->fresh()->getAttributes());
        $this->assertDatabaseCount('activity_logs', 0);
        $this->assertSame('update', $preview->error_details['rows'][0]['action']);
        $run = $service->apply($preview);
        $product->refresh();
        $this->assertSame(10001, $product->price);
        $this->assertSame('1.125', $product->stock);
        $this->assertSame('00123', $product->sku);
        foreach (['name', 'description', 'status', 'featured', 'slug', 'external_id', 'purchase_mode', 'sale_unit'] as $field) {
            $this->assertEquals($before[$field] ?? null, $product->getRawOriginal($field));
        }
        $this->assertSame($image->getAttributes(), $image->fresh()->getAttributes());
        $this->assertSame($item->getAttributes(), $item->fresh()->getAttributes());
        $this->assertSame([$category->id], $product->categories->modelKeys());
        $this->assertTrue($product->isExternallyManaged());
        $this->assertSame(1, $run->updated_count);
        $this->assertSame('completed', $run->status->value);
        $this->assertDatabaseHas('activity_logs', ['module' => 'products', 'origin' => 'external', 'actor_id' => auth()->id()]);
        $this->assertDatabaseHas('activity_logs', ['module' => 'sync']);
    }

    public function test_new_products_are_drafts_and_repeated_data_is_idempotent(): void
    {
        $service = $this->service();
        $records = [['sku' => '0007', 'kind' => 'product', 'name' => 'Ficticio', 'sale_unit' => 'unit', 'price_cents' => 0, 'stock' => '0']];
        $run = $service->apply($service->preview($records));
        $product = Product::sole();
        $this->assertSame('0007', $product->sku);
        $this->assertSame('draft', $product->status->value);
        $this->assertSame('inquiry', $product->purchase_mode->value);
        $this->assertSame(1, $run->created_count);
        $before = $product->getAttributes();
        $again = $service->apply($service->preview($records));
        $this->assertSame(0, $again->created_count + $again->updated_count);
        $this->assertSame(1, $again->error_details['skipped_count']);
        $this->assertDatabaseCount('products', 1);
        $this->assertSame($before, $product->fresh()->getAttributes());
        $this->assertSame(1, ActivityLog::where('module', 'products')->count());
    }

    public function test_duplicates_cross_table_ambiguity_and_invalid_rows_are_skipped(): void
    {
        $service = $this->service();
        Product::factory()->create(['sku' => 'SHARED']);
        ProductVariant::factory()->create(['sku' => ' SHARED ']);
        $records = [
            ['sku' => 'SHARED', 'kind' => 'product', 'price_cents' => 1],
            ['sku' => 'DUP', 'kind' => 'product', 'price_cents' => 2],
            ['sku' => ' DUP ', 'kind' => 'product', 'price_cents' => -1],
            ['sku' => 123, 'kind' => 'product', 'price_cents' => 1],
            ['sku' => '', 'kind' => 'product', 'stock' => '1'],
            ['sku' => 'FLOAT', 'kind' => 'product', 'price_cents' => 1.25],
            ['sku' => 'STOCK', 'kind' => 'product', 'stock' => '1,234'],
            ['sku' => 'SECRET', 'kind' => 'product', 'price_cents' => 3, 'api_key' => 'do-not-save'],
        ];
        $preview = $service->preview($records);
        $this->assertSame('conflict', $preview->error_details['rows'][0]['action']);
        $this->assertSame('conflict', $preview->error_details['rows'][1]['action']);
        $this->assertStringNotContainsString('do-not-save', json_encode($preview->error_details));
        $run = $service->apply($preview);
        $this->assertSame(8, $run->error_count);
        $this->assertSame(0, $run->created_count + $run->updated_count);
    }

    public function test_variant_matching_new_inactive_variants_and_parent_protection(): void
    {
        $service = $this->service();
        $parent = Product::factory()->variable()->create(['sku' => 'PARENT']);
        $variant = ProductVariant::factory()->create(['product_id' => $parent->id, 'sku' => 'V-01']);
        $run = $service->apply($service->preview([
            ['sku' => 'V-01', 'kind' => 'variant', 'price_cents' => 999],
            ['sku' => 'V-02', 'kind' => 'variant', 'parent_sku' => 'PARENT', 'name' => 'Nueva', 'stock' => '2.500'],
            ['sku' => 'PARENT', 'kind' => 'product', 'price_cents' => 123],
        ]));
        $this->assertSame(999, $variant->fresh()->price);
        $this->assertSame($variant->stock, $variant->fresh()->stock);
        $this->assertFalse(ProductVariant::where('sku', 'V-02')->sole()->active);
        $this->assertNull($parent->fresh()->price);
        $this->assertSame(1, $run->created_count);
        $this->assertSame(1, $run->updated_count);
        $this->assertSame(1, $run->error_count);
    }

    public function test_stale_preview_cannot_overwrite_a_concurrent_change(): void
    {
        $service = $this->service();
        $product = Product::factory()->create(['sku' => 'STALE']);
        $preview = $service->preview([['sku' => 'STALE', 'kind' => 'product', 'price_cents' => 1]]);
        $product->update(['price' => 999]);
        try {
            $service->apply($preview);
            $this->fail('A stale preview must be rejected.');
        } catch (ValidationException) {
            $this->assertSame(999, $product->fresh()->price);
        }
    }

    public function test_failure_rolls_back_all_changes_and_records_a_sanitized_failure(): void
    {
        $service = $this->service();
        $product = Product::factory()->create(['sku' => 'ROLLBACK']);
        $preview = $service->preview([['sku' => 'ROLLBACK', 'kind' => 'product', 'price_cents' => 1]]);
        $this->partialMock(ActivityLogService::class, function ($mock): void {
            $mock->shouldReceive('record')->andThrow(new \RuntimeException('secret-provider-message'));
        });
        $run = $service->apply($preview);
        $this->assertSame('failed', $run->status->value);
        $this->assertSame($product->price, $product->fresh()->price);
        $this->assertNull($product->fresh()->external_synced_at);
        $this->assertDatabaseCount('activity_logs', 0);
        $this->assertStringNotContainsString('secret-provider-message', json_encode($run->error_details));
    }

    public function test_admin_upload_preview_and_explicit_confirmation(): void
    {
        $this->service();
        $json = '[{"sku":"HTTP-01","kind":"product","name":"Prueba","sale_unit":"unit","price_cents":125}]';
        $this->get('/admin/commercial-sync')->assertOk();
        $this->post('/admin/commercial-sync/preview', ['file' => UploadedFile::fake()->createWithContent('sample.json', $json)])->assertRedirect();
        $preview = SyncRun::sole();
        $this->assertDatabaseCount('products', 0);
        $this->get('/admin/commercial-sync?run='.$preview->id)->assertOk()->assertSee('HTTP-01');
        $this->post('/admin/commercial-sync/'.$preview->id.'/apply')->assertSessionHasErrors('confirm');
        $this->post('/admin/commercial-sync/'.$preview->id.'/apply', ['confirm' => 1])->assertRedirect();
        $this->assertDatabaseCount('products', 1);
        $this->post('/admin/commercial-sync/'.$preview->id.'/apply', ['confirm' => 1])->assertSessionHasErrors('preview');
        $this->assertDatabaseCount('products', 1);
    }

    public function test_non_admins_cannot_read_preview_or_apply(): void
    {
        $this->get('/admin/commercial-sync')->assertRedirect('/login');
        $service = $this->service();
        $preview = $service->preview([['sku' => 'A', 'kind' => 'product', 'price_cents' => 1]]);
        foreach (['seller', 'customer'] as $role) {
            $this->flushSession();
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->get('/admin/commercial-sync')->assertForbidden();
            $this->post('/admin/commercial-sync/preview')->assertForbidden();
            $this->post('/admin/commercial-sync/'.$preview->id.'/apply', ['confirm' => 1])->assertForbidden();
        }
    }

    public function test_upload_limits_and_malformed_json_are_rejected(): void
    {
        $this->service();
        foreach ([['bad.json', '{'], ['bad.csv', 'sku,price'], ['bad.json', str_repeat(' ', 263000)], ['many.json', json_encode(array_fill(0, 101, []))]] as [$name, $content]) {
            $this->post('/admin/commercial-sync/preview', ['file' => UploadedFile::fake()->createWithContent($name, $content)])->assertSessionHasErrors('file');
        }
        $this->assertDatabaseCount('sync_runs', 0);
    }

    public function test_expired_or_other_admin_preview_is_rejected(): void
    {
        $service = $this->service();
        $preview = $service->preview([['sku' => 'A', 'kind' => 'product', 'price_cents' => 1]]);
        $this->travel(31)->minutes();
        $this->post('/admin/commercial-sync/'.$preview->id.'/apply', ['confirm' => 1])->assertSessionHasErrors('preview');
        $this->travelBack();
        $this->flushSession();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post('/admin/commercial-sync/'.$preview->id.'/apply', ['confirm' => 1])->assertSessionHasErrors('preview');
    }

    public function test_invalid_commercial_values_are_not_coerced(): void
    {
        $service = $this->service();
        foreach ([['price_cents' => '100'], ['price_cents' => null], ['stock' => 1.25], ['stock' => '-1'], ['stock' => '1.0001'], ['stock' => '1000000000000'], ['price_cents' => 1000000000000000]] as $fields) {
            $preview = $service->preview([['sku' => 'INVALID', 'kind' => 'product', ...$fields]]);
            $this->assertSame('invalid', $preview->error_details['rows'][0]['action']);
        }
        $this->assertSame('00a', $service->normalizeSku(' 00a '));
        $this->assertNotSame($service->normalizeSku('abc'), $service->normalizeSku('ABC'));
    }

    public function test_documented_fixture_covers_existing_new_duplicate_variant_and_invalid_records(): void
    {
        $service = $this->service();
        Product::factory()->create(['sku' => '00123']);
        ProductVariant::factory()->create(['sku' => 'VAR-001']);
        $records = $service->decode(file_get_contents(base_path('../docs/sync-demo.json')));
        $preview = $service->preview($records);
        $this->assertSame(['update', 'create', 'conflict', 'conflict', 'update', 'invalid'], array_column($preview->error_details['rows'], 'action'));
        $run = $service->apply($preview);
        $this->assertSame(1, $run->created_count);
        $this->assertSame(2, $run->updated_count);
        $this->assertSame(3, $run->error_count);
    }

    public function test_ambiguous_normalization_wrong_kind_and_units_do_not_modify_catalog(): void
    {
        $service = $this->service();
        Product::factory()->create(['sku' => 'AMB']);
        Product::factory()->create(['sku' => ' AMB ']);
        Product::factory()->create(['sku' => 'UNIT', 'sale_unit' => 'unit']);
        $preview = $service->preview([
            ['sku' => 'AMB', 'kind' => 'product', 'price_cents' => 1],
            ['sku' => 'UNIT', 'kind' => 'variant', 'price_cents' => 1],
            ['sku' => 'MISSING', 'kind' => 'variant', 'parent_sku' => 'UNIT', 'name' => 'Variante', 'price_cents' => 1],
        ]);
        $this->assertSame(['conflict', 'conflict', 'conflict'], array_column($preview->error_details['rows'], 'action'));
        $preview = $service->preview([['sku' => 'UNIT', 'kind' => 'product', 'sale_unit' => 'meter', 'price_cents' => 1]]);
        $this->assertSame('conflict', $preview->error_details['rows'][0]['action']);
    }

    public function test_service_itself_rejects_unauthorized_actors(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'seller']));
        $this->expectException(AuthorizationException::class);
        app(CommercialSyncService::class)->preview([['sku' => 'A', 'kind' => 'product', 'price_cents' => 1]]);
    }
}
