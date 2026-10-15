<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_dashboard_and_pagination_only_include_own_orders(): void
    {
        $user = User::factory()->create();
        $other = Order::factory()->create(['order_number' => 'PEDIDO-AJENO']);
        Order::factory()->count(12)->create(['user_id' => $user->id]);
        $this->actingAs($user)->get(route('account.edit'))->assertOk()->assertSee('Mis pedidos')->assertSee($user->email)->assertDontSee('PEDIDO-AJENO')->assertViewHas('recentOrders', fn ($orders) => $orders->count() === 5);
        $this->get(route('account.orders.index', ['user_id' => $other->user_id]))->assertOk()->assertDontSee('PEDIDO-AJENO')->assertViewHas('orders', fn ($orders) => $orders->count() === 10 && $orders->total() === 12);
        $this->get(route('account.orders.index', ['page' => 2]))->assertOk()->assertViewHas('orders', fn ($orders) => $orders->count() === 2);
    }

    public function test_customer_cannot_access_other_or_guest_order_by_id_or_query(): void
    {
        $user = User::factory()->create();
        $other = Order::factory()->create();
        $guest = Order::factory()->guest()->create(['customer_email' => $user->email]);
        $this->actingAs($user)->get(route('account.orders.show', $other))->assertNotFound();
        $this->get(route('account.orders.show', ['order' => $guest->id, 'user_id' => $user->id]))->assertNotFound();
        $this->get('/mi-cuenta/pedidos/999999')->assertNotFound();
        $this->assertDatabaseMissing('orders', ['id' => $guest->id, 'user_id' => $user->id]);
    }

    public function test_detail_uses_snapshots_and_simplified_history_without_staff_identity(): void
    {
        $user = User::factory()->create();
        $staff = User::factory()->seller()->create(['first_name' => 'InternoPrivado', 'last_name' => 'Operador']);
        $product = Product::factory()->create(['name' => 'Producto nuevo']);
        $order = Order::factory()->create(['user_id' => $user->id, 'status' => 'ready_for_pickup', 'has_items_to_confirm' => true]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id, 'product_name' => 'Producto histórico', 'variant_name' => '4 L', 'sale_unit' => 'meter', 'quantity' => '5.500', 'unit_price' => null, 'subtotal' => null, 'requires_confirmation' => true]);
        $order->statusHistory()->create(['previous_status' => 'preparing', 'new_status' => 'ready_for_pickup', 'changed_by' => $staff->id]);
        $product->delete();
        $this->actingAs($user)->get(route('account.orders.show', $order))->assertOk()->assertSee('Producto histórico')->assertSee('4 L')->assertSee('5,5')->assertSee('A confirmar')->assertSee('Listo para retirar')->assertSee('Historial de estados')->assertDontSee('InternoPrivado')->assertDontSee('Producto nuevo');
    }

    public function test_cancelled_order_does_not_show_false_progress(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Cancelled]);
        $this->actingAs($order->user)->get(route('account.orders.show', $order))->assertOk()->assertSee('Este pedido fue cancelado.')->assertDontSee('Progreso del pedido');
    }

    public function test_account_is_customer_only_but_staff_keep_own_profile(): void
    {
        $this->get(route('account.orders.index'))->assertRedirect(route('login'));
        $staff = User::factory()->seller()->create();
        $this->actingAs($staff)->get(route('account.edit'))->assertForbidden();
        $this->get(route('account.orders.index'))->assertForbidden();
        $this->get(route('account.notifications.index'))->assertForbidden();
        $this->get(route('admin.profile.edit'))->assertOk()->assertSee($staff->email);
        $this->patch(route('admin.profile.update'), ['first_name' => 'Vendedor', 'last_name' => $staff->last_name, 'email' => $staff->email, 'phone' => $staff->phone])->assertSessionHasNoErrors();
        $this->assertSame('Vendedor', $staff->fresh()->first_name);
    }

    public function test_profile_changes_do_not_rewrite_historical_contact(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'customer_first_name' => 'Nombre original']);
        $this->actingAs($user)->patch(route('account.update'), ['first_name' => 'Nombre nuevo', 'last_name' => $user->last_name, 'email' => $user->email, 'phone' => $user->phone])->assertSessionHasNoErrors();
        $this->assertSame('Nombre original', $order->fresh()->customer_first_name);
    }
}
