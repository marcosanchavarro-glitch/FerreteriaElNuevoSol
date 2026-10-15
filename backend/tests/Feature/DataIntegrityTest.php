<?php

namespace Tests\Feature;

use App\Enums\ProductType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\DevelopmentSeeder;
use DomainException;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DataIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_cannot_be_mass_assigned_from_profile_data(): void
    {
        $this->expectException(MassAssignmentException::class);
        User::create(['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@example.test', 'password' => 'test', 'role' => UserRole::Admin]);
    }

    public function test_default_user_role_is_customer(): void
    {
        $user = User::create(['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@example.test', 'password' => 'test']);
        $this->assertSame(UserRole::Customer, $user->fresh()->role);
    }

    public static function invalidMoney(): array
    {
        return [[1.25], ['1.25'], [-1], ['1e5'], [true], ['1000000000000000']];
    }

    #[DataProvider('invalidMoney')]
    public function test_money_rejects_non_integer_or_out_of_range_values(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        Product::factory()->create(['price' => $value]);
    }

    public static function invalidQuantities(): array
    {
        return [[10.5], ['1.0001'], ['NaN'], ['1e3'], ['1,5'], [true], ['1000000000000']];
    }

    #[DataProvider('invalidQuantities')]
    public function test_quantities_reject_lossy_or_invalid_input(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        Product::factory()->create(['stock' => $value]);
    }

    public function test_signed_fractional_stock_and_maximum_values_are_exact(): void
    {
        $product = Product::factory()->create(['price' => 999999999999999, 'stock' => '-0.001']);
        $this->assertSame('-0.001', $product->fresh()->stock);
        $product->update(['stock' => '999999999999.999']);
        $this->assertSame('999999999999.999', $product->fresh()->stock);
        $this->assertSame(999999999999999, $product->fresh()->price);
    }

    public function test_order_item_cannot_have_zero_quantity(): void
    {
        $this->expectException(DomainException::class);
        OrderItem::factory()->create(['quantity' => '0']);
    }

    public function test_order_item_cannot_have_negative_quantity(): void
    {
        $this->expectException(DomainException::class);
        OrderItem::factory()->create(['quantity' => '-0.001']);
    }

    public function test_item_cannot_reference_a_variant_from_another_product(): void
    {
        $variant = ProductVariant::factory()->create();
        $other = Product::factory()->create();
        $this->expectException(DomainException::class);
        OrderItem::factory()->create(['product_id' => $other->id, 'product_variant_id' => $variant->id]);
    }

    public function test_enum_cast_rejects_unknown_product_status(): void
    {
        $this->expectException(\ValueError::class);
        Product::factory()->create(['status' => 'not-a-status']);
    }

    public function test_variable_parent_cannot_store_commercial_totals(): void
    {
        $this->expectException(DomainException::class);
        Product::factory()->variable()->create(['price' => 100]);
    }

    public function test_variants_require_a_variable_parent(): void
    {
        $product = Product::factory()->create();
        $this->expectException(DomainException::class);
        ProductVariant::factory()->for($product)->create();
    }

    public function test_product_with_variants_cannot_become_simple(): void
    {
        $variant = ProductVariant::factory()->create();
        $this->expectException(DomainException::class);
        $variant->product->update(['product_type' => ProductType::Simple]);
    }

    public function test_category_cannot_be_its_own_parent(): void
    {
        $category = Category::factory()->create();
        $this->expectException(DomainException::class);
        $category->update(['parent_id' => $category->id]);
    }

    public function test_category_cannot_move_below_its_descendant(): void
    {
        $a = Category::factory()->create();
        $b = Category::factory()->for($a, 'parent')->create();
        $c = Category::factory()->for($b, 'parent')->create();
        $this->expectException(DomainException::class);
        $a->update(['parent_id' => $c->id]);
    }

    public function test_product_category_pivot_rejects_duplicates(): void
    {
        $product = Product::factory()->create();
        $category = Category::factory()->create();
        $product->categories()->attach($category);
        $this->expectException(QueryException::class);
        $product->categories()->attach($category);
    }

    public function test_notification_recipient_pivot_rejects_duplicates(): void
    {
        $notification = Notification::factory()->create();
        $user = User::factory()->create();
        $notification->users()->attach($user);
        $this->expectException(QueryException::class);
        $notification->users()->attach($user);
    }

    public function test_missing_product_foreign_key_is_rejected(): void
    {
        $this->expectException(QueryException::class);
        DB::table('product_images')->insert(['product_id' => 999999, 'path' => 'missing.jpg']);
    }

    public function test_orders_with_items_cannot_be_cascade_deleted(): void
    {
        $item = OrderItem::factory()->create();
        $this->expectException(QueryException::class);
        $item->order->delete();
    }

    public function test_orders_with_status_history_cannot_be_cascade_deleted(): void
    {
        $history = OrderStatusHistory::factory()->create();
        $this->expectException(QueryException::class);
        $history->order->delete();
    }

    public function test_order_number_is_unique(): void
    {
        Order::factory()->create(['order_number' => 'TEST-1']);
        $this->expectException(QueryException::class);
        Order::factory()->create(['order_number' => 'TEST-1']);
    }

    public function test_product_slug_is_unique(): void
    {
        Product::factory()->create(['slug' => 'test']);
        $this->expectException(QueryException::class);
        Product::factory()->create(['slug' => 'test']);
    }

    public function test_external_ids_and_skus_are_not_assumed_unique(): void
    {
        Product::factory()->count(2)->create(['sku' => 'UNKNOWN', 'external_id' => 'UNKNOWN']);
        $this->assertDatabaseCount('products', 2);
    }

    public function test_development_seed_is_blocked_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        try {
            $this->expectException(LogicException::class);
            $this->seed(DevelopmentSeeder::class);
        } finally {
            $this->app->detectEnvironment(fn () => 'testing');
        }
    }
}
