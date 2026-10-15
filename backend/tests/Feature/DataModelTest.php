<?php

namespace Tests\Feature;

use App\Enums\FulfillmentMethod;
use App\Enums\HomepageSectionType;
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\PurchaseMode;
use App\Enums\SaleUnit;
use App\Enums\SyncRunStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Banner;
use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\SyncRun;
use App\Models\User;
use Database\Seeders\DevelopmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DataModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrations_create_all_domain_tables_with_foreign_keys_enabled(): void
    {
        foreach (['users', 'products', 'product_variants', 'categories', 'category_product', 'product_images', 'orders', 'order_items', 'order_status_history', 'notifications', 'notification_user', 'homepage_sections', 'banners', 'settings', 'sync_runs', 'activity_logs'] as $table) {
            $this->assertTrue(Schema::hasTable($table), $table);
        }
        $this->assertContains(DB::connection()->getDriverName(), ['sqlite', 'pgsql']);
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->assertTrue(DB::connection()->getConfig('foreign_key_constraints'));
        }
        $this->assertNotEmpty(Schema::getForeignKeys('order_items'));
        $this->assertNotEmpty(Schema::getIndexes('category_product'));
    }

    public function test_users_have_hashed_passwords_and_enum_roles(): void
    {
        foreach ([UserRole::Admin, UserRole::Seller, UserRole::Customer] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertSame($role, $user->fresh()->role);
            $this->assertTrue(Hash::check('SoloDesarrollo!2026', $user->password));
            $this->assertNotSame('SoloDesarrollo!2026', $user->password);
            $this->assertArrayNotHasKey('password', $user->toArray());
            $this->assertTrue($user->fresh()->active);
        }
    }

    public function test_catalog_money_and_fractional_stock_round_trip_exactly(): void
    {
        $product = Product::factory()->create(['price' => 100001, 'stock' => '10.501', 'sale_unit' => SaleUnit::Kilogram]);
        $fresh = $product->fresh();
        $this->assertSame(100001, $fresh->price);
        $this->assertSame('10.501', $fresh->stock);
        $this->assertSame(10501, DB::table('products')->where('id', $product->id)->value('stock'));
        $this->assertSame(ProductStatus::Draft, $fresh->status);
        $this->assertSame(ProductType::Simple, $fresh->product_type);
        $this->assertSame(SaleUnit::Kilogram, $fresh->sale_unit);
        $this->assertSame(PurchaseMode::Calculated, $fresh->purchase_mode);
        $this->assertNull(Product::factory()->inquiry()->create()->fresh()->price);
    }

    public function test_variable_product_owns_three_independent_variants(): void
    {
        $product = Product::factory()->variable()->create();
        foreach (['1 L', '4 L', '20 L'] as $name) {
            ProductVariant::factory()->for($product)->create(['name' => $name]);
        }
        $this->assertCount(3, $product->variants);
        $this->assertNull($product->price);
        $this->assertNull($product->stock);
        $this->assertTrue($product->variants->first()->product->is($product));
        $variant = $product->variants->last();
        $variant->update(['price' => 990001, 'stock' => '1.125', 'purchase_mode' => PurchaseMode::Inquiry, 'sale_unit' => SaleUnit::Liter]);
        $this->assertSame(990001, $variant->fresh()->price);
        $this->assertSame('1.125', $variant->fresh()->stock);
        $this->assertNull($product->fresh()->price);
    }

    public function test_categories_have_unbounded_depth_and_many_to_many_products(): void
    {
        $root = Category::factory()->create();
        $second = Category::factory()->for($root, 'parent')->create();
        $third = Category::factory()->for($second, 'parent')->create();
        $fourth = Category::factory()->for($third, 'parent')->create();
        $product = Product::factory()->create();
        $product->categories()->attach([$root->id, $fourth->id]);
        $this->assertTrue($fourth->parent->parent->parent->is($root));
        $this->assertTrue($root->children->first()->is($second));
        $this->assertCount(2, $product->categories);
        $this->assertTrue($fourth->products->first()->is($product));
    }

    public function test_image_metadata_is_related_without_binary_storage(): void
    {
        $product = Product::factory()->create();
        $image = ProductImage::factory()->for($product)->create(['path' => 'products/demo.webp', 'is_primary' => true, 'sort_order' => 2]);
        $this->assertTrue($image->product->is($product));
        $this->assertSame('products/demo.webp', $product->images->first()->path);
        $this->assertTrue($image->fresh()->is_primary);
        $this->assertSame(2, $image->fresh()->sort_order);
    }

    public function test_registered_and_guest_orders_have_independent_customer_snapshots(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['customer_first_name' => 'Nombre histórico']);
        $guest = Order::factory()->guest()->create();
        $this->assertTrue($order->user->is($user));
        $this->assertTrue($user->orders->first()->is($order));
        $this->assertNull($guest->user);
        $this->assertSame(FulfillmentMethod::Pickup, $guest->fresh()->fulfillment_method);
        $this->assertSame(OrderStatus::Received, $guest->fresh()->status);
        $user->update(['first_name' => 'Otro nombre']);
        $this->assertSame('Nombre histórico', $order->fresh()->customer_first_name);
    }

    public function test_order_snapshots_survive_product_variant_and_user_changes_and_deletions(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['subtotal' => 100002, 'confirmed_total' => 100002]);
        $product = Product::factory()->variable()->create();
        $variant = ProductVariant::factory()->for($product)->create();
        $item = OrderItem::factory()->for($order)->create([
            'product_id' => $product->id, 'product_variant_id' => $variant->id,
            'product_name' => 'Pintura histórica', 'variant_name' => '4 L', 'sku' => 'ANTERIOR',
            'quantity' => '1.500', 'sale_unit' => SaleUnit::Liter, 'unit_price' => 66668, 'subtotal' => 100002,
        ]);
        $history = OrderStatusHistory::factory()->for($order)->create(['changed_by' => $user->id]);
        $this->assertTrue($item->productVariant->is($variant));
        $this->assertTrue($item->product->is($product));
        $this->assertTrue($order->items->first()->is($item));
        $this->assertTrue($order->statusHistory->first()->is($history));
        $this->assertTrue($history->changedBy->is($user));
        $product->update(['name' => 'Renombrado']);
        $variant->update(['price' => 999999, 'name' => 'Nuevo']);
        $this->assertSame(66668, $item->fresh()->unit_price);
        $product->delete();
        $user->delete();
        $item->refresh();
        $this->assertNull($item->product_id);
        $this->assertNull($item->product_variant_id);
        $this->assertNull($order->fresh()->user_id);
        $this->assertNull($history->fresh()->changed_by);
        $this->assertSame('Pintura histórica', $item->product_name);
        $this->assertSame('4 L', $item->variant_name);
        $this->assertSame('ANTERIOR', $item->sku);
        $this->assertSame('1.500', $item->quantity);
        $this->assertSame(SaleUnit::Liter, $item->sale_unit);
        $this->assertSame(100002, $item->subtotal);
        $this->assertSame(100002, $order->fresh()->confirmed_total);
    }

    public function test_inquiry_items_keep_unknown_amounts_null(): void
    {
        $item = OrderItem::factory()->create(['unit_price' => null, 'subtotal' => null, 'requires_confirmation' => true]);
        $this->assertNull($item->fresh()->unit_price);
        $this->assertNull($item->fresh()->subtotal);
        $this->assertTrue($item->fresh()->requires_confirmation);
    }

    public function test_notification_read_state_is_individual_and_creator_is_optional(): void
    {
        $creator = User::factory()->admin()->create();
        $a = User::factory()->create();
        $b = User::factory()->create();
        $notification = Notification::factory()->create(['created_by' => $creator->id, 'published_at' => now(), 'expires_at' => now()->addDay()]);
        $notification->users()->attach([$a->id, $b->id]);
        $notification->users()->updateExistingPivot($a->id, ['read_at' => now()]);
        $this->assertNotNull($a->notifications->first()->pivot->read_at);
        $this->assertNull($b->notifications->first()->pivot->read_at);
        $this->assertTrue($notification->creator->is($creator));
        $creator->delete();
        $this->assertNull($notification->fresh()->created_by);
    }

    public function test_content_sync_metadata_and_audit_models_persist(): void
    {
        $section = HomepageSection::factory()->create();
        $this->assertSame(HomepageSectionType::Hero, $section->fresh()->type);
        $this->assertSame(['alignment' => 'left'], $section->fresh()->settings);
        $banner = Banner::factory()->create(['starts_at' => now(), 'ends_at' => now()->addDay()]);
        $this->assertTrue($banner->fresh()->ends_at->greaterThan($banner->starts_at));
        $this->assertNotEmpty(Setting::factory()->create()->fresh()->value);
        $sync = SyncRun::factory()->create(['error_details' => ['message' => 'Test without credentials']]);
        $this->assertSame(SyncRunStatus::Running, $sync->fresh()->status);
        $this->assertSame('Test without credentials', $sync->fresh()->error_details['message']);
        $audit = ActivityLog::factory()->create();
        $this->assertTrue($audit->user->activityLogs->first()->is($audit));
        $audit->user->delete();
        $this->assertNull($audit->fresh()->user_id);
        $this->assertSame(['test' => true], $audit->fresh()->metadata);
        $this->assertNotNull($audit->created_at);
        $this->assertArrayNotHasKey('updated_at', $audit->getAttributes());
    }

    public function test_development_seed_is_repeatable_without_duplicates(): void
    {
        $this->seed(DevelopmentSeeder::class);
        $this->seed(DevelopmentSeeder::class);
        $this->assertDatabaseCount('users', 4);
        $this->assertDatabaseCount('categories', 4);
        $this->assertDatabaseCount('products', 4);
        $this->assertDatabaseCount('product_variants', 3);
        $this->assertSame(1, User::where('role', UserRole::Admin)->count());
        $this->assertSame(1, User::where('role', UserRole::Seller)->count());
        $this->assertSame(2, User::where('role', UserRole::Customer)->count());
        $this->assertSame('25.500', Product::where('slug', 'cable-demo')->firstOrFail()->stock);
    }
}
