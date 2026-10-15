<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Audit\ActivityLogService;
use App\Services\Catalog\CommercialSyncService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CommercialSyncPostgresTest extends TestCase
{
    use DatabaseMigrations;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Requires isolated PostgreSQL 17 test database.');
        }
        $this->assertTrue(app()->environment('testing'));
        $this->assertSame('ens_test', DB::connection()->getDatabaseName());
        $this->assertContains(DB::connection()->getConfig('host'), ['127.0.0.1', 'localhost']);
    }

    public function test_apply_holds_row_locks_and_hides_changes_until_commit(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $product = Product::factory()->create(['sku' => 'PG-001', 'price' => 100]);
        $variant = ProductVariant::factory()->create(['sku' => 'PG-V001', 'price' => 200]);
        $service = app(CommercialSyncService::class);
        $preview = $service->preview([
            ['sku' => $product->sku, 'kind' => 'product', 'price_cents' => 101],
            ['sku' => $variant->sku, 'kind' => 'variant', 'price_cents' => 201],
        ]);
        config(['database.connections.lock_probe' => config('database.connections.pgsql')]);
        $probe = DB::connection('lock_probe');
        $audit = app(ActivityLogService::class);
        $observedPrices = $lockStates = [];
        $this->partialMock(ActivityLogService::class, function ($mock) use ($probe, $product, $variant, $preview, $audit, &$observedPrices, &$lockStates): void {
            $mock->shouldReceive('record')->andReturnUsing(function (...$arguments) use ($probe, $product, $variant, $preview, $audit, &$observedPrices, &$lockStates) {
                if ($observedPrices === []) {
                    $observedPrices = [
                        (int) $probe->table('products')->where('id', $product->id)->value('price'),
                        (int) $probe->table('product_variants')->where('id', $variant->id)->value('price'),
                    ];
                    foreach (['products' => $product->id, 'product_variants' => $variant->id, 'sync_runs' => $preview->id] as $table => $id) {
                        try {
                            $probe->table($table)->where('id', $id)->lock('FOR UPDATE NOWAIT')->first();
                            $lockStates[$table] = 'unlocked';
                        } catch (QueryException $exception) {
                            $lockStates[$table] = $exception->errorInfo[0];
                        }
                    }
                }

                return $audit->record(...$arguments);
            });
        });
        try {
            $run = $service->apply($preview);
            $this->assertSame('completed', $run->status->value);
            $this->assertSame([100, 200], $observedPrices);
            $this->assertSame(['products' => '55P03', 'product_variants' => '55P03', 'sync_runs' => '55P03'], $lockStates);
            $this->assertSame(101, (int) $probe->table('products')->where('id', $product->id)->value('price'));
            $this->assertSame(201, (int) $probe->table('product_variants')->where('id', $variant->id)->value('price'));
            $this->assertNotNull($probe->table('sync_runs')->where('id', $preview->id)->lock('FOR UPDATE NOWAIT')->first());
        } finally {
            DB::purge('lock_probe');
        }
    }
}
