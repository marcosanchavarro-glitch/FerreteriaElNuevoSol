<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Orders\OrderNotifications;
use App\Services\Orders\OrderStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private function notice(User $user, array $attributes = []): Notification
    {
        $notification = Notification::factory()->create($attributes + ['active' => true, 'published_at' => now()]);
        $notification->users()->attach($user);

        return $notification;
    }

    public function test_checkout_creates_received_notice_only_once_and_skips_guests(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['status' => 'published']);
        $this->actingAs($user)->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => '1']);
        $this->get(route('checkout.create'));
        $data = ['first_name' => 'Ana', 'last_name' => 'Pérez', 'phone' => '1155551234', 'token' => session('checkout.token')];
        $this->post(route('checkout.store'), $data)->assertSessionHasNoErrors();
        $this->post(route('checkout.store'), $data)->assertSessionHasNoErrors();
        $order = Order::sole();
        $notice = Notification::sole();
        $this->assertSame('order.received', $notice->type);
        $this->assertSame('Recibimos tu pedido #'.$order->order_number.'.', $notice->message);
        $this->assertSame($order->id, $notice->order_id);
        $this->assertDatabaseHas('notification_user', ['notification_id' => $notice->id, 'user_id' => $user->id, 'read_at' => null]);
        app(OrderNotifications::class)->record(Order::factory()->guest()->create());
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_ready_and_cancelled_notices_link_to_owned_order_and_do_not_send_external_messages(): void
    {
        Mail::fake();
        Http::preventStrayRequests();
        $staff = User::factory()->seller()->create();
        $order = Order::factory()->create(['status' => 'preparing']);
        $service = app(OrderStatusService::class);
        $service->change($order, $staff, OrderStatus::Preparing, OrderStatus::ReadyForPickup);
        $notice = Notification::sole();
        $this->assertSame('Tu pedido #'.$order->order_number.' está listo para retirar.', $notice->message);
        $this->assertSame($staff->id, $notice->created_by);
        $this->actingAs($order->user)->get(route('account.notifications.index'))->assertOk()->assertSee($notice->message)->assertSee(route('account.orders.show', $order), false);
        $service->change($order, $staff, OrderStatus::ReadyForPickup, OrderStatus::Cancelled);
        $this->assertDatabaseHas('notifications', ['order_id' => $order->id, 'type' => 'order.cancelled', 'message' => 'El pedido #'.$order->order_number.' fue cancelado.']);
        Mail::assertNothingSent();
    }

    public function test_unread_count_read_one_read_all_and_cross_user_isolation(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $a = $this->notice($user, ['title' => 'Aviso propio']);
        $b = $this->notice($user);
        $foreign = $this->notice($other, ['title' => 'Notificación ajena']);
        $this->actingAs($user)->get(route('account.notifications.index'))->assertOk()->assertSee('Notificaciones: 2 sin leer')->assertDontSee('Notificación ajena');
        $this->patch(route('account.notifications.read', $foreign), ['user_id' => $other->id])->assertNotFound();
        $this->patch(route('account.notifications.read', $a))->assertSessionHasNoErrors();
        $readAt = $user->notifications()->findOrFail($a->id)->pivot->read_at;
        $this->assertNotNull($readAt);
        $this->travel(1)->hours();
        $this->patch(route('account.notifications.read', $a))->assertSessionHasNoErrors();
        $this->assertSame($readAt, $user->notifications()->findOrFail($a->id)->pivot->read_at);
        $this->get(route('home'))->assertSee('Notificaciones: 1 sin leer');
        $this->post(route('account.notifications.read-all'), ['user_id' => $other->id])->assertSessionHasNoErrors();
        $this->get(route('account.edit'))->assertSee('Notificaciones: 0 sin leer');
        $this->assertNull($other->notifications()->findOrFail($foreign->id)->pivot->read_at);
        $this->assertNotNull($user->notifications()->findOrFail($b->id)->pivot->read_at);
    }

    public function test_visibility_expiry_plain_text_and_pagination(): void
    {
        $user = User::factory()->create();
        $hidden = $this->notice($user, ['title' => 'OCULTA', 'active' => false]);
        $this->notice($user, ['title' => 'FUTURA', 'published_at' => now()->addDay()]);
        $this->notice($user, ['title' => 'VENCIDA', 'expires_at' => now()->subSecond()]);
        $this->notice($user, ['title' => '<script>alert(1)</script>']);
        for ($i = 0; $i < 16; $i++) {
            $this->notice($user);
        }
        $this->actingAs($user)->get(route('account.notifications.index'))->assertOk()->assertDontSee('OCULTA')->assertDontSee('FUTURA')->assertDontSee('VENCIDA')->assertViewHas('notifications', fn ($items) => $items->count() === 15 && $items->total() === 17);
        $this->get(route('account.notifications.index', ['page' => 2]))->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->patch(route('account.notifications.read', $hidden))->assertNotFound();
        $this->post(route('account.notifications.read-all'));
        $this->assertNull($user->notifications()->findOrFail($hidden->id)->pivot->read_at);
    }

    public function test_shared_future_information_read_state_is_per_recipient(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $notice = $this->notice($user);
        $notice->users()->attach($other);
        $this->actingAs($user)->post(route('account.notifications.read-all'))->assertSessionHasNoErrors();
        $this->assertNull($other->notifications()->findOrFail($notice->id)->pivot->read_at);
        $this->assertNotNull($user->notifications()->findOrFail($notice->id)->pivot->read_at);
    }

    public function test_notification_failure_rolls_back_checkout_and_preserves_cart(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('cart.store'), ['product_id' => Product::factory()->create(['status' => 'published'])->id, 'quantity' => '1']);
        $this->get(route('checkout.create'));
        Notification::creating(function (): void {
            throw new \RuntimeException('Notification failure');
        });
        $this->withoutExceptionHandling();
        try {
            $this->post(route('checkout.store'), ['first_name' => 'Ana', 'last_name' => 'Pérez', 'phone' => '1155551234', 'token' => session('checkout.token')]);
            $this->fail('Expected failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('Notification failure', $e->getMessage());
        } finally {
            Notification::flushEventListeners();
        }
        foreach (['orders', 'order_items', 'order_status_history', 'notifications', 'notification_user'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertNotEmpty(session('cart'));
    }
}
