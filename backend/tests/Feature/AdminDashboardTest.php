<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public static function staff(): array
    {
        return [['admin'], ['seller']];
    }

    #[DataProvider('staff')]
    public function test_staff_dashboard_navigation_and_existing_modules(string $role): void
    {
        $this->actingAs(User::factory()->create(['role' => $role]));
        $response = $this->get('/admin')->assertOk()->assertSee('Dashboard')->assertSee('Página de inicio')->assertSee('Banners')->assertSee('aria-current="page"', false);
        if ($role === 'admin') {
            $response->assertSee('Configuración');
        } else {
            $response->assertDontSee('Configuración');
        }
        foreach (['products', 'categories', 'orders', 'content/sections', 'content/banners'] as $path) {
            $this->get('/admin/'.$path)->assertOk();
        }
    }

    public function test_guest_and_customer_are_blocked(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }

    public function test_dashboard_counts_known_stock_and_recent_orders_without_loading_entire_history(): void
    {
        Product::factory()->create(['status' => 'published', 'stock' => '0']);
        Product::factory()->create(['status' => 'published', 'stock' => null]);
        Product::factory()->create(['status' => 'published', 'stock' => '4']);
        Product::factory()->create();
        $out = Product::factory()->variable()->create(['status' => 'published']);
        ProductVariant::factory()->create(['product_id' => $out->id, 'stock' => '0']);
        ProductVariant::factory()->create(['product_id' => $out->id, 'stock' => '10', 'active' => false]);
        $unknown = Product::factory()->variable()->create(['status' => 'published']);
        ProductVariant::factory()->create(['product_id' => $unknown->id, 'stock' => null]);
        Order::factory()->guest()->count(9)->create(['status' => 'received']);
        Order::factory()->guest()->create(['status' => 'preparing']);
        Order::factory()->guest()->create(['status' => 'ready_for_pickup']);
        $latest = Order::factory()->guest()->create(['status' => 'cancelled']);
        $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk()
            ->assertViewHas('productStats', fn ($s) => $s === ['published' => 5, 'out_of_stock' => 2, 'draft' => 1, 'without_image' => 5])
            ->assertViewHas('orderStats', fn ($s) => $s['received'] === 9 && $s['preparing'] === 1 && $s['ready_for_pickup'] === 1)
            ->assertViewHas('recentOrders', fn ($orders) => $orders->count() === 8 && $orders->first()->is($latest));
    }
}
