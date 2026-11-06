<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private function data(array $overrides = []): array
    {
        return array_merge(['name' => 'Taladro de prueba', 'slug' => 'taladro-test', 'product_type' => 'simple', 'sale_unit' => 'unit', 'purchase_mode' => 'inquiry', 'status' => 'draft', 'featured' => '0', 'description' => 'Descripción web'], $overrides);
    }

    private function webData(array $overrides = []): array
    {
        return array_merge(['slug' => 'externo-web', 'purchase_mode' => 'calculated', 'status' => 'draft', 'featured' => '0', 'description' => 'Nueva descripción'], $overrides);
    }

    public static function staff(): array
    {
        return [['admin'], ['seller']];
    }

    #[DataProvider('staff')]
    public function test_staff_can_access_list_create_and_edit_web_information(string $role): void
    {
        $user = User::factory()->create(['role' => UserRole::from($role)]);
        $this->actingAs($user)->get('/admin')->assertOk();
        $this->get('/admin/products')->assertOk();
        $this->get('/admin/products/create')->assertOk();
        $this->post('/admin/products', $this->data())->assertRedirect();
        $product = Product::sole();
        $this->assertNull($product->external_id);
        $this->assertNull($product->price);
        $this->get('/admin/products/'.$product->id)->assertOk()->assertSee('Descripción web');
        $this->get('/admin/products/'.$product->id.'/edit')->assertOk();
        $this->put('/admin/products/'.$product->id, $this->data(['status' => 'published', 'featured' => '1', 'name' => 'Actualizado']))->assertRedirect();
        $this->assertSame(ProductStatus::Published, $product->fresh()->status);
        $this->assertTrue($product->fresh()->featured);
        $this->put('/admin/products/'.$product->id, $this->data(['status' => 'private', 'featured' => '0']))->assertRedirect();
        $this->assertSame(ProductStatus::Private, $product->fresh()->status);
        $this->assertFalse($product->fresh()->featured);
    }

    public function test_customer_cannot_list_create_or_modify_products(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $this->actingAs($user)->get('/admin/products')->assertForbidden();
        $this->get('/admin/products/create')->assertForbidden();
        $this->post('/admin/products', $this->data())->assertForbidden();
        $this->get('/admin/products/'.$product->id)->assertForbidden();
        $this->put('/admin/products/'.$product->id, $this->data())->assertForbidden();
    }

    public function test_admin_can_set_local_money_and_fractional_stock_exactly(): void
    {
        $this->actingAs(User::factory()->admin()->create())->post('/admin/products', $this->data(['price' => '2500,51', 'stock' => '10,501', 'sku' => 'LOCAL-1', 'purchase_mode' => 'calculated', 'status' => 'published']))->assertRedirect();
        $product = Product::sole();
        $this->assertSame(250051, $product->price);
        $this->assertSame('10.501', $product->stock);
        $this->assertSame('LOCAL-1', $product->sku);
        $this->get('/admin/products/'.$product->id)->assertSee('$ 2.500,51');
    }

    public static function sellerFields(): array
    {
        return [['price', '100'], ['stock', '99'], ['sku', 'FAKE'], ['external_id', 'EXTERNAL'], ['external_synced_at', '2026-01-01'], ['role', 'admin'], ['id', 55], ['price', null]];
    }

    #[DataProvider('sellerFields')]
    public function test_seller_cannot_write_protected_fields_on_create_or_update(string $field, mixed $value): void
    {
        $seller = User::factory()->seller()->create();
        $product = Product::factory()->create();
        $price = $product->price;
        $stock = $product->stock;
        $this->actingAs($seller)->post('/admin/products', $this->data([$field => $value]))->assertForbidden();
        $this->put('/admin/products/'.$product->id, $this->data([$field => $value]))->assertForbidden();
        $this->assertSame($price, $product->fresh()->price);
        $this->assertSame($stock, $product->fresh()->stock);
        $this->assertDatabaseCount('products', 1);
    }

    public static function externalFields(): array
    {
        $cases = [];
        foreach (['admin', 'seller'] as $role) {
            foreach (['price', 'stock', 'sku', 'external_id', 'external_synced_at', 'name', 'product_type', 'sale_unit'] as $field) {
                $cases[] = [$role, $field];
            }
        }

        return $cases;
    }

    #[DataProvider('externalFields')]
    public function test_external_commercial_data_is_read_only_for_all_staff(string $role, string $field): void
    {
        $product = Product::factory()->create(['external_id' => 'EXTERNAL-1', 'external_synced_at' => now()]);
        $before = $product->fresh()->getAttributes();
        $this->actingAs(User::factory()->create(['role' => UserRole::from($role)]))->put('/admin/products/'.$product->id, $this->webData([$field => 'tampered']))->assertForbidden();
        $this->assertSame($before, $product->fresh()->getAttributes());
    }

    #[DataProvider('staff')]
    public function test_staff_can_edit_external_editorial_fields_and_fields_are_not_editable_in_html(string $role): void
    {
        $product = Product::factory()->create(['external_id' => 'EXT']);
        $this->actingAs(User::factory()->create(['role' => UserRole::from($role)]))->get('/admin/products/'.$product->id.'/edit')->assertOk()->assertDontSee('name="price"', false)->assertDontSee('name="stock"', false);
        $this->put('/admin/products/'.$product->id, $this->webData(['status' => 'published', 'featured' => '1']))->assertRedirect();
        $this->assertSame('Nueva descripción', $product->fresh()->description);
        $this->assertSame('EXT', $product->fresh()->external_id);
        $this->assertTrue($product->fresh()->featured);
    }

    public function test_sync_timestamp_without_external_id_is_still_protected(): void
    {
        $product = Product::factory()->create(['external_synced_at' => now()]);
        $this->actingAs(User::factory()->admin()->create())->put('/admin/products/'.$product->id, $this->webData(['price' => '123']))->assertForbidden();
    }

    public function test_listing_paginates_and_searches_by_name_and_sku(): void
    {
        Product::factory()->count(17)->create(['name' => 'Producto genérico', 'sku' => null]);
        $wanted = Product::factory()->create(['name' => 'Taladro especial', 'sku' => 'BUSCAR-42']);
        $this->actingAs(User::factory()->seller()->create())->get('/admin/products')->assertOk()->assertViewHas('products', fn ($products) => $products->count() === 15 && $products->total() === 18);
        $this->get('/admin/products?page=2')->assertViewHas('products', fn ($products) => $products->count() === 3);
        foreach (['Taladro especial', 'BUSCAR-42'] as $query) {
            $this->get('/admin/products?q='.urlencode($query))->assertViewHas('products', fn ($products) => $products->total() === 1 && $products->first()->is($wanted));
        }
    }

    public function test_invalid_fields_are_rejected_without_creating_product(): void
    {
        $this->actingAs(User::factory()->admin()->create())->post('/admin/products', $this->data(['slug' => 'Bad Slug', 'status' => 'deleted', 'sale_unit' => 'fake', 'purchase_mode' => 'free', 'featured' => 'yes', 'price' => '1.001', 'stock' => '1.0001']))->assertSessionHasErrors(['slug', 'status', 'sale_unit', 'purchase_mode', 'featured', 'price', 'stock']);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_duplicate_slug_is_rejected(): void
    {
        Product::factory()->create(['slug' => 'taladro-test']);
        $this->actingAs(User::factory()->seller()->create())->post('/admin/products', $this->data())->assertSessionHasErrors('slug');
    }

    public function test_calculated_product_without_price_cannot_be_published(): void
    {
        $this->actingAs(User::factory()->seller()->create())->post('/admin/products', $this->data(['purchase_mode' => 'calculated', 'status' => 'published']))->assertSessionHasErrors('status');
    }

    public function test_variable_parent_has_no_price_and_cannot_become_simple_with_variants(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post('/admin/products', $this->data(['product_type' => 'variable', 'price' => '100']))->assertSessionHasErrors('product_type');
        $this->post('/admin/products', $this->data(['product_type' => 'variable', 'price' => null, 'stock' => null]))->assertRedirect();
        $product = Product::sole();
        ProductVariant::factory()->for($product)->create();
        $this->put('/admin/products/'.$product->id, $this->data(['product_type' => 'simple']))->assertSessionHasErrors('product_type');
    }

    public function test_edits_preserve_order_snapshots_and_no_delete_route_exists(): void
    {
        $product = Product::factory()->create();
        $item = OrderItem::factory()->for($product)->create();
        $snapshot = $item->fresh()->getAttributes();
        $this->actingAs(User::factory()->admin()->create())->put('/admin/products/'.$product->id, $this->data(['price' => '9999.99', 'stock' => '3']))->assertRedirect();
        $this->assertSame($snapshot, $item->fresh()->getAttributes());
        $this->delete('/admin/products/'.$product->id)->assertStatus(405);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_stored_markup_is_escaped_in_product_views(): void
    {
        $product = Product::factory()->create(['name' => '<script>alert(1)</script>', 'description' => '<img src=x onerror=alert(1)>']);
        $this->actingAs(User::factory()->seller()->create())->get('/admin/products/'.$product->id)->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false)->assertDontSee('<img src=x', false);
    }

    public function test_product_workspace_filters_and_unified_editor(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::factory()->create();
        $match = Product::factory()->create(['name' => 'Cable seleccionado', 'stock' => '2.500', 'featured' => true, 'purchase_mode' => 'calculated', 'status' => 'published']);
        $match->categories()->attach($category);
        Product::factory()->create(['name' => 'Producto excluido', 'stock' => null, 'featured' => false]);
        $this->actingAs($user)->get('/admin/products?'.http_build_query(['q' => 'Cable', 'category' => $category->id, 'mode' => 'calculated', 'status' => 'published', 'availability' => 'available', 'featured' => '1', 'sort' => 'name']))
            ->assertOk()->assertViewHas('products', fn ($products) => $products->total() === 1 && $products->first()->is($match));
        $this->get('/admin/products?availability=unknown')->assertOk()->assertViewHas('products', fn ($products) => $products->total() === 1);
        $this->get('/admin/products?availability=empty')->assertOk()->assertViewHas('products', fn ($products) => $products->total() === 0);
        $this->get('/admin/products/'.$match->id.'/edit')->assertOk()->assertSee('data-editor-panel="categories"', false)->assertSee('data-editor-panel="images"', false);
        $this->get('/admin/products?availability=invalid')->assertSessionHasErrors('availability');
    }

    public function test_quick_actions_authorize_audit_and_preserve_commercial_values(): void
    {
        $product = Product::factory()->create(['external_id' => 'external-test', 'price' => 1000, 'stock' => '4.000', 'featured' => false, 'status' => 'draft']);
        $this->actingAs(User::factory()->create())->post('/admin/products/'.$product->id.'/quick', ['action' => 'featured'])->assertForbidden();
        $this->flushSession();
        $this->actingAs(User::factory()->create(['role' => UserRole::Seller]));
        $this->post('/admin/products/'.$product->id.'/quick', ['action' => 'featured', 'price' => '0'])->assertRedirect();
        $this->assertTrue($product->fresh()->featured);
        $this->assertSame(1000, $product->fresh()->price);
        $this->assertSame('4.000', $product->fresh()->stock);
        $this->assertDatabaseHas('activity_logs', ['module' => 'products', 'entity_id' => $product->id]);
        $product->update(['price' => null, 'purchase_mode' => 'calculated']);
        $this->post('/admin/products/'.$product->id.'/quick', ['action' => 'visibility'])->assertSessionHasErrors('status');
        $this->assertSame(ProductStatus::Draft, $product->fresh()->status);
    }

    public function test_product_list_query_count_does_not_grow_per_product(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        Product::factory()->create();
        DB::enableQueryLog();
        $this->get('/admin/products')->assertOk();
        $first = count(DB::getQueryLog());
        if (getenv('ENS_PROFILE')) {
            fwrite(STDERR, 'SQL listado 1 producto: '.$first.' consultas, '.array_sum(array_column(DB::getQueryLog(), 'time')).' ms'.PHP_EOL);
        }
        DB::disableQueryLog();
        Product::factory()->count(10)->create();
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get('/admin/products')->assertOk();
        $this->assertLessThanOrEqual($first, count(DB::getQueryLog()));
        if (getenv('ENS_PROFILE')) {
            fwrite(STDERR, 'SQL listado 11 productos: '.count(DB::getQueryLog()).' consultas, '.array_sum(array_column(DB::getQueryLog(), 'time')).' ms'.PHP_EOL);
        }
        DB::disableQueryLog();
    }

    public function test_simple_editor_does_not_query_unused_variants(): void
    {
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        DB::enableQueryLog();
        $this->get('/admin/products/'.$product->id.'/edit')->assertOk();
        $this->assertFalse(collect(DB::getQueryLog())->contains(fn ($query) => str_contains($query['query'], 'product_variants')));
        DB::disableQueryLog();
    }

    public function test_editor_json_save_confirms_commit_without_render_redirect(): void
    {
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
        $this->putJson('/admin/products/'.$product->id, $this->data(['name' => 'Guardado progresivo']))
            ->assertOk()->assertExactJson(['saved' => true])->assertHeaderMissing('Location');
        $this->assertSame('Guardado progresivo', $product->fresh()->name);
        $this->putJson('/admin/products/'.$product->id, $this->data(['product_type' => 'invalid']))->assertUnprocessable()->assertJsonValidationErrors('product_type');
        $this->assertSame('Guardado progresivo', $product->fresh()->name);
    }
}
