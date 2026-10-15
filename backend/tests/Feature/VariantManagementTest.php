<?php

namespace Tests\Feature;

use App\Enums\PurchaseMode;
use App\Enums\SaleUnit;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VariantManagementTest extends TestCase
{
    use RefreshDatabase;

    private function data(array $changes = []): array
    {
        return array_merge(['name' => 'Pintura 4 L', 'value' => '4 L', 'sale_unit' => 'liter', 'purchase_mode' => 'inquiry', 'active' => '1'], $changes);
    }

    private function base(Product $product): string
    {
        return '/admin/products/'.$product->id.'/variants';
    }

    public function test_admin_creates_edits_and_deactivates_local_variant_with_exact_amounts(): void
    {
        $product = Product::factory()->variable()->create();
        $base = $this->base($product);
        $this->actingAs(User::factory()->admin()->create())->get($base.'/create')->assertOk();
        $this->post($base, $this->data(['sku' => 'ALBA-4', 'price' => '8500,51', 'stock' => '0,750', 'purchase_mode' => 'calculated']))->assertRedirect();
        $variant = $product->variants()->sole();
        $this->assertSame(850051, $variant->price);
        $this->assertSame('0.750', $variant->stock);
        $this->assertSame($product->id, $variant->product_id);
        $this->assertSame(SaleUnit::Liter, $variant->effectiveSaleUnit());
        $this->get($base)->assertOk()->assertSee('ALBA-4')->assertSee('Litro');
        $this->get($base.'/'.$variant->id)->assertOk()->assertSee('$ 8.500,51');
        $this->get($base.'/'.$variant->id.'/edit')->assertOk();
        $snapshot = OrderItem::factory()->create(['product_id' => $product->id, 'product_variant_id' => $variant->id, 'variant_name' => 'Presentación histórica']);
        $this->put($base.'/'.$variant->id, $this->data(['name' => 'Presentación 4 L', 'active' => '0', 'sku' => 'ALBA-4', 'price' => '12000.01', 'stock' => '2.501']))->assertRedirect();
        $variant->refresh();
        $this->assertFalse($variant->active);
        $this->assertSame(1200001, $variant->price);
        $this->assertSame('2.501', $variant->stock);
        $this->assertSame('Presentación histórica', $snapshot->fresh()->variant_name);
        $this->put($base.'/'.$variant->id, $this->data(['active' => '1']))->assertRedirect();
        $this->assertTrue($variant->fresh()->active);
        $this->delete($base.'/'.$variant->id)->assertStatus(405);
        $this->assertModelExists($variant);
    }

    public function test_seller_can_manage_local_web_fields_without_changing_commercial_values(): void
    {
        $product = Product::factory()->variable()->create();
        $base = $this->base($product);
        $this->actingAs(User::factory()->seller()->create())->post($base, $this->data())->assertRedirect();
        $variant = $product->variants()->sole();
        $this->assertNull($variant->price);
        $this->assertNull($variant->stock);
        $this->assertNull($variant->sku);
        $variant->update(['price' => 105051, 'stock' => '10.001', 'sku' => 'KEPT']);
        $this->put($base.'/'.$variant->id, $this->data(['name' => 'Nueva presentación', 'active' => '0']))->assertRedirect();
        $variant->refresh();
        $this->assertSame('Nueva presentación', $variant->name);
        $this->assertSame(105051, $variant->price);
        $this->assertSame('10.001', $variant->stock);
        $this->assertSame('KEPT', $variant->sku);
        $this->assertFalse($variant->active);
    }

    public static function protectedFields(): array
    {
        return [['sku', 'BAD'], ['price', '100'], ['stock', '1.234'], ['price', null], ['external_id', 'EX'], ['external_synced_at', '2026-01-01'], ['product_id', 999], ['id', 999], ['role', 'admin']];
    }

    #[DataProvider('protectedFields')]
    public function test_seller_cannot_forge_protected_values(string $field, mixed $value): void
    {
        $variant = ProductVariant::factory()->create();
        $base = $this->base($variant->product);
        $this->actingAs(User::factory()->seller()->create())->post($base, $this->data([$field => $value]))->assertForbidden();
        $this->put($base.'/'.$variant->id, $this->data([$field => $value]))->assertForbidden();
        $this->assertDatabaseCount('product_variants', 1);
        $this->assertSame(245000, $variant->fresh()->price);
    }

    public static function externalOrigins(): array
    {
        return [['admin', 'parent'], ['seller', 'parent'], ['admin', 'variant'], ['seller', 'variant'], ['admin', 'timestamp'], ['seller', 'timestamp']];
    }

    #[DataProvider('externalOrigins')]
    public function test_external_variants_are_read_only_for_all_staff(string $role, string $source): void
    {
        $variant = ProductVariant::factory()->create();
        if ($source === 'parent') {
            $variant->product->update(['external_id' => 'EX-P']);
        } elseif ($source === 'timestamp') {
            $variant->update(['external_synced_at' => now()]);
        } else {
            $variant->update(['external_id' => 'EX-V']);
        }
        $base = $this->base($variant->product);
        $this->actingAs(User::factory()->create(['role' => $role]))->get($base.'/'.$variant->id)->assertOk()->assertSee('Información sincronizada');
        $this->get($base.'/'.$variant->id.'/edit')->assertForbidden();
        $this->put($base.'/'.$variant->id, $this->data(['price' => '1', 'stock' => '999', 'sku' => 'BAD']))->assertForbidden();
        $this->put($base.'/'.$variant->id, $this->data(['active' => '0']))->assertForbidden();
        $this->assertSame(245000, $variant->fresh()->price);
        $this->assertTrue($variant->fresh()->active);
        if ($source === 'parent') {
            $this->post($base, $this->data())->assertForbidden();
        }
    }

    public static function invalidValues(): array
    {
        return [['price', '1.001'], ['price', '-1'], ['price', '1e3'], ['price', '99999999999999'], ['stock', '0.0001'], ['stock', '-0.001'], ['stock', '1e2'], ['stock', '9999999999999'], ['active', 'yes'], ['sale_unit', 'bad'], ['purchase_mode', 'bad'], ['name', ''], ['sku', str_repeat('x', 256)]];
    }

    #[DataProvider('invalidValues')]
    public function test_invalid_values_are_rejected(string $field, string $value): void
    {
        $product = Product::factory()->variable()->create();
        $this->actingAs(User::factory()->admin()->create())->postJson($this->base($product), $this->data([$field => $value]))->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertDatabaseCount('product_variants', 0);
    }

    public function test_sku_is_unique_only_within_parent_and_unchanged_sku_is_allowed(): void
    {
        $first = ProductVariant::factory()->create(['sku' => 'SAME']);
        $other = Product::factory()->variable()->create();
        $base = $this->base($first->product);
        $this->actingAs(User::factory()->admin()->create())->postJson($base, $this->data(['sku' => 'SAME']))->assertUnprocessable()->assertJsonValidationErrors('sku');
        $this->put($base.'/'.$first->id, $this->data(['sku' => 'SAME']))->assertRedirect();
        $this->post($this->base($other), $this->data(['sku' => 'SAME']))->assertRedirect();
        $this->post($base, $this->data(['sku' => null]))->assertRedirect();
        $this->post($base, $this->data(['sku' => null]))->assertRedirect();
        $this->assertDatabaseCount('product_variants', 4);
    }

    public function test_inheritance_and_active_calculated_price_requirement(): void
    {
        $product = Product::factory()->variable()->create(['sale_unit' => 'meter', 'purchase_mode' => 'calculated']);
        $base = $this->base($product);
        $this->actingAs(User::factory()->admin()->create())->postJson($base, $this->data(['sale_unit' => null, 'purchase_mode' => null]))->assertUnprocessable()->assertJsonValidationErrors('price');
        $this->post($base, $this->data(['sale_unit' => null, 'purchase_mode' => null, 'price' => '0.00']))->assertRedirect();
        $variant = $product->variants()->sole();
        $this->assertSame(SaleUnit::Meter, $variant->effectiveSaleUnit());
        $this->assertSame(PurchaseMode::Calculated, $variant->effectivePurchaseMode());
        $this->assertSame(0, $variant->price);
        $this->post($base, $this->data(['purchase_mode' => null, 'active' => '0']))->assertRedirect();
    }

    public function test_simple_parent_and_wrong_parent_are_blocked(): void
    {
        $variant = ProductVariant::factory()->create();
        $simple = Product::factory()->create();
        $other = Product::factory()->variable()->create();
        $this->actingAs(User::factory()->admin()->create())->post($this->base($simple), $this->data())->assertForbidden();
        $this->get($this->base($other).'/'.$variant->id)->assertNotFound();
        $this->put($this->base($other).'/'.$variant->id, $this->data())->assertNotFound();
        $this->post('/admin/products/999999/variants', $this->data())->assertNotFound();
    }

    public function test_customer_and_guest_are_blocked(): void
    {
        $variant = ProductVariant::factory()->create();
        $base = $this->base($variant->product);
        $this->get($base)->assertRedirect('/login');
        $this->actingAs(User::factory()->create());
        foreach ([$base, $base.'/create', $base.'/'.$variant->id, $base.'/'.$variant->id.'/edit'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->post($base, $this->data())->assertForbidden();
        $this->put($base.'/'.$variant->id, $this->data())->assertForbidden();
    }

    public function test_omitted_overrides_are_preserved_and_explicit_null_inherits_parent(): void
    {
        $product = Product::factory()->variable()->create(['sale_unit' => 'meter', 'purchase_mode' => 'calculated']);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'sale_unit' => 'liter', 'purchase_mode' => 'inquiry', 'price' => null]);
        $url = $this->base($product).'/'.$variant->id;
        $this->actingAs(User::factory()->seller()->create())->put($url, ['name' => 'Presentación web', 'active' => '1'])->assertRedirect();
        $this->assertSame(PurchaseMode::Inquiry, $variant->fresh()->effectivePurchaseMode());
        $this->assertSame(SaleUnit::Liter, $variant->fresh()->effectiveSaleUnit());
        $this->putJson($url, ['name' => 'Presentación web', 'active' => '1', 'purchase_mode' => null])->assertUnprocessable()->assertJsonValidationErrors('price');
        $this->put($url, ['name' => 'Presentación web', 'active' => '0', 'purchase_mode' => null, 'sale_unit' => null])->assertRedirect();
        $this->assertSame(PurchaseMode::Calculated, $variant->fresh()->effectivePurchaseMode());
        $this->assertSame(SaleUnit::Meter, $variant->fresh()->effectiveSaleUnit());
    }

    public function test_changing_parent_mode_cannot_make_inherited_active_variants_calculated_without_price(): void
    {
        $product = Product::factory()->variable()->create(['purchase_mode' => 'inquiry']);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price' => null, 'purchase_mode' => null, 'active' => true]);
        $data = ['name' => $product->name, 'slug' => $product->slug, 'product_type' => 'variable', 'sale_unit' => 'unit', 'purchase_mode' => 'calculated', 'status' => 'draft', 'featured' => '0'];
        $this->actingAs(User::factory()->admin()->create())->putJson('/admin/products/'.$product->id, $data)->assertUnprocessable()->assertJsonValidationErrors('purchase_mode');
        $this->assertSame(PurchaseMode::Inquiry, $product->fresh()->purchase_mode);
        $variant->update(['purchase_mode' => 'inquiry']);
        $this->put('/admin/products/'.$product->id, $data)->assertRedirect();
        $this->assertSame(PurchaseMode::Calculated, $product->fresh()->purchase_mode);
    }
}
