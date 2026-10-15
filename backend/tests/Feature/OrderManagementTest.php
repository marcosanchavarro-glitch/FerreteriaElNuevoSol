<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\User;
use App\Services\Orders\OrderStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    public static function staffRoles(): array
    {
        return [['admin'], ['seller']];
    }

    #[DataProvider('staffRoles')]
    public function test_staff_can_list_view_and_manage_guest_orders(string $role): void
    {
        $staff = User::factory()->create(['role' => $role]);
        $order = Order::factory()->guest()->create();
        $this->actingAs($staff)->get(route('admin.orders.index'))->assertOk()->assertSee($order->order_number)->assertSee('Invitado');
        $this->get(route('admin.orders.show', $order))->assertOk()->assertSee($order->customer_phone);
        $this->patch(route('admin.orders.update', $order), ['expected_status' => 'received', 'status' => 'preparing', 'user_id' => 999])->assertSessionHasNoErrors();
        $this->assertSame(OrderStatus::Preparing, $order->fresh()->status);
        $history = $order->statusHistory()->sole();
        $this->assertSame($staff->id, $history->changed_by);
        $this->assertSame(OrderStatus::Received, $history->previous_status);
        $this->assertSame(OrderStatus::Preparing, $history->new_status);
        $this->assertDatabaseCount('notifications', 0);
        $this->get(route('admin.orders.show', $order))->assertSee($staff->first_name.' '.$staff->last_name);
    }

    public function test_customer_and_guest_cannot_access_admin_orders(): void
    {
        $order = Order::factory()->create();
        $this->get(route('admin.orders.index'))->assertRedirect(route('login'));
        $this->actingAs($order->user)->get(route('admin.orders.index'))->assertForbidden();
        $this->get(route('admin.orders.show', $order))->assertForbidden();
        $this->patch(route('admin.orders.update', $order), ['expected_status' => 'received', 'status' => 'preparing'])->assertForbidden();
        $this->assertSame(OrderStatus::Received, $order->fresh()->status);
        $this->assertDatabaseCount('order_status_history', 0);
    }

    public static function transitions(): array
    {
        $allowed = ['received' => ['preparing', 'cancelled'], 'preparing' => ['ready_for_pickup', 'cancelled'], 'ready_for_pickup' => ['picked_up', 'cancelled'], 'picked_up' => [], 'cancelled' => []];
        $cases = [];
        foreach (OrderStatus::cases() as $from) {
            foreach (OrderStatus::cases() as $to) {
                $cases[$from->value.' to '.$to->value] = [$from->value, $to->value, in_array($to->value, $allowed[$from->value], true)];
            }
        }

        return $cases;
    }

    #[DataProvider('transitions')]
    public function test_transition_matrix(string $from, string $to, bool $allowed): void
    {
        $staff = User::factory()->seller()->create();
        $order = Order::factory()->create(['status' => $from]);
        $data = ['expected_status' => $from, 'status' => $to];
        if ($to === 'cancelled') {
            $data['confirm_cancel'] = '1';
        }
        $response = $this->actingAs($staff)->patch(route('admin.orders.update', $order), $data);
        if ($allowed) {
            $response->assertSessionHasNoErrors();
            $this->assertSame($to, $order->fresh()->status->value);
            $this->assertDatabaseCount('order_status_history', 1);
            $this->assertDatabaseCount('notifications', 1);
            $this->assertDatabaseHas('notification_user', ['user_id' => $order->user_id]);
        } else {
            $response->assertSessionHasErrors('status');
            $this->assertSame($from, $order->fresh()->status->value);
            $this->assertDatabaseCount('order_status_history', 0);
            $this->assertDatabaseCount('notifications', 0);
        }
    }

    public function test_cancellation_requires_explicit_confirmation_and_stale_forms_are_rejected(): void
    {
        $order = Order::factory()->create();
        $this->actingAs(User::factory()->admin()->create());
        $this->patch(route('admin.orders.update', $order), ['expected_status' => 'received', 'status' => 'cancelled'])->assertSessionHasErrors('confirm_cancel');
        $this->patch(route('admin.orders.update', $order), ['expected_status' => 'received', 'status' => 'preparing'])->assertSessionHasNoErrors();
        $this->patch(route('admin.orders.update', $order), ['expected_status' => 'received', 'status' => 'cancelled', 'confirm_cancel' => 1])->assertSessionHasErrors('status');
        $this->assertSame('preparing', $order->fresh()->status->value);
        $this->assertDatabaseCount('notifications', 1);
    }

    public static function searches(): array
    {
        return [['ENS-BUSCAR'], ['Ana Pérez'], ['1144441234'], ['ana.perez@example.test']];
    }

    #[DataProvider('searches')]
    public function test_search_uses_customer_snapshot_fields(string $q): void
    {
        $order = Order::factory()->guest()->create(['order_number' => 'ENS-BUSCAR', 'customer_first_name' => 'Ana', 'customer_last_name' => 'Pérez', 'customer_phone' => '1144441234', 'customer_email' => 'ana.perez@example.test']);
        $other = Order::factory()->create(['order_number' => 'ENS-OTRO']);
        $this->actingAs(User::factory()->seller()->create())->get(route('admin.orders.index', compact('q')))->assertOk()->assertSee($order->order_number)->assertDontSee($other->order_number);
    }

    public function test_filters_date_timezone_pagination_and_validation(): void
    {
        $order = Order::factory()->guest()->create(['order_number' => 'ENS-FILTRO', 'status' => 'ready_for_pickup', 'created_at' => '2026-09-25 01:30:00']);
        $other = Order::factory()->create(['order_number' => 'ENS-FUERA', 'created_at' => '2026-09-25 05:00:00']);
        $this->actingAs(User::factory()->admin()->create());
        $this->get(route('admin.orders.index', ['status' => 'ready_for_pickup', 'customer' => 'guest', 'from' => '2026-09-24', 'to' => '2026-09-24']))->assertOk()->assertSee($order->order_number)->assertDontSee($other->order_number);
        $this->get(route('admin.orders.index', ['to' => '2026-09-24']))->assertOk()->assertSee($order->order_number)->assertDontSee($other->order_number);
        $this->get(route('admin.orders.index', ['from' => '2026-09-25', 'to' => '2026-09-24']))->assertSessionHasErrors();
        $this->get(route('admin.orders.index', ['status' => 'arbitrary']))->assertSessionHasErrors();
        $this->get(route('admin.orders.index', ['from' => '2020-01-01', 'to' => '2026-01-01']))->assertSessionHasErrors();
        Order::factory()->count(21)->guest()->create();
        $this->get(route('admin.orders.index'))->assertOk()->assertViewHas('orders', fn ($orders) => $orders->count() === 20 && $orders->total() === 23);
    }

    public function test_admin_detail_uses_snapshots_after_catalog_deletion(): void
    {
        $order = Order::factory()->create(['has_items_to_confirm' => true]);
        $product = Product::factory()->create(['name' => 'Nombre actual']);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id, 'product_name' => 'Nombre histórico', 'variant_name' => '4 L', 'sku' => 'SKU-HISTORICO', 'requires_confirmation' => true, 'unit_price' => null, 'subtotal' => null]);
        $product->delete();
        $this->actingAs(User::factory()->seller()->create())->get(route('admin.orders.show', $order))->assertOk()->assertSee('Nombre histórico')->assertSee('SKU-HISTORICO')->assertSee('4 L')->assertSee('A confirmar')->assertDontSee('Nombre actual');
    }

    public static function failingWrites(): array
    {
        return [[OrderStatusHistory::class], [Notification::class]];
    }

    #[DataProvider('failingWrites')]
    public function test_failure_rolls_back_state_history_and_notification(string $model): void
    {
        $order = Order::factory()->create();
        $staff = User::factory()->admin()->create();
        $model::creating(function (): void {
            throw new \RuntimeException('Simulated failure');
        });
        try {
            app(OrderStatusService::class)->change($order, $staff, OrderStatus::Received, OrderStatus::Preparing);
            $this->fail('Expected failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('Simulated failure', $e->getMessage());
        } finally {
            $model::flushEventListeners();
        }
        $this->assertSame(OrderStatus::Received, $order->fresh()->status);
        $this->assertDatabaseCount('order_status_history', 0);
        $this->assertDatabaseCount('notifications', 0);
        $this->assertDatabaseCount('notification_user', 0);
    }
}
