<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Storefront\WhatsAppOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CartCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $attributes = []): Product
    {
        return Product::factory()->create($attributes + ['status' => 'published', 'price' => 10001, 'stock' => '20.000']);
    }

    private function add(Product $product, string $quantity = '1', ?int $variant = null): TestResponse
    {
        return $this->post(route('cart.store'), ['product_id' => $product->id, 'product_variant_id' => $variant, 'quantity' => $quantity, 'price' => 1, 'subtotal' => 1]);
    }

    private function contact(): array
    {
        return ['first_name' => 'Ana', 'last_name' => 'Pérez', 'phone' => '+54 11 5555-1234', 'email' => null, 'token' => session('checkout.token')];
    }

    public function test_simple_accumulates_updates_and_removes(): void
    {
        $p = $this->product();
        $this->add($p, '2')->assertRedirect(route('cart.index'));
        $this->add($p)->assertSessionHas('cart.'.$p->id.'-0.quantity', '3.000');
        $this->get(route('cart.index'))->assertOk()->assertSee('$ 300,03')->assertSee('1 productos distintos');
        $this->patch(route('cart.update', $p->id.'-0'), ['quantity' => '4', 'product_id' => 999])->assertSessionHas('cart.'.$p->id.'-0.quantity', '4.000');
        $this->delete(route('cart.destroy', $p->id.'-0'))->assertRedirect();
        $this->get(route('cart.index'))->assertSee('Tu carrito está vacío.');
    }

    public static function badQuantities(): array
    {
        return array_map(fn ($q) => [$q], ['0', '-1', 'NaN', '1e2', '1.0001', '10000', '0.000', '1.5', [], '999999999999999999']);
    }

    #[DataProvider('badQuantities')]
    public function test_rejects_bad_quantities(mixed $quantity): void
    {
        $p = $this->product();
        $this->post(route('cart.store'), ['product_id' => $p->id, 'quantity' => $quantity])->assertSessionHasErrors();
        $this->assertEmpty(session('cart', []));
    }

    public static function unavailableProducts(): array
    {
        return [['draft', '20'], ['private', '20'], ['published', '0'], ['published', '0.500']];
    }

    #[DataProvider('unavailableProducts')]
    public function test_rejects_hidden_or_insufficient_stock(string $status, string $stock): void
    {
        $this->add($this->product(compact('status', 'stock')))->assertSessionHasErrors();
        $this->assertEmpty(session('cart', []));
    }

    public function test_invalid_ids_and_variants_are_rejected_and_distinct_variants_are_separate(): void
    {
        $p = Product::factory()->variable()->create(['status' => 'published']);
        $a = ProductVariant::factory()->create(['product_id' => $p->id, 'active' => true, 'stock' => '10', 'price' => 20000]);
        $b = ProductVariant::factory()->create(['product_id' => $p->id, 'active' => true, 'stock' => '10', 'price' => 30000]);
        $other = ProductVariant::factory()->create(['active' => true]);
        $this->add($p)->assertSessionHasErrors();
        $this->add($p, '1', $other->id)->assertSessionHasErrors();
        $this->add($p, '1', 99999)->assertSessionHasErrors();
        $this->post(route('cart.store'), ['product_id' => 999999, 'quantity' => '1'])->assertSessionHasErrors();
        $this->add($this->product(), '1', $a->id)->assertSessionHasErrors();
        $this->add($p, '2', $a->id)->assertRedirect(route('cart.index'));
        $this->add($p, '1', $b->id)->assertRedirect(route('cart.index'));
        $this->assertCount(2, session('cart'));
        $this->get(route('cart.index'))->assertSee('$ 700,00');
    }

    public function test_guest_mixed_order_exact_totals_snapshots_history_and_idempotency(): void
    {
        $p = $this->product(['sale_unit' => 'meter', 'name' => 'Cable & cobre']);
        $inquiry = $this->product(['purchase_mode' => 'inquiry', 'price' => 88888, 'stock' => '0']);
        $this->add($p, '5,5');
        $this->add($inquiry);
        $this->get(route('checkout.create'))->assertOk()->assertSee('550,06');
        $data = $this->contact() + ['price' => 1, 'subtotal' => 1, 'confirmed_total' => 1, 'user_id' => 999, 'status' => 'completed', 'fulfillment_method' => 'delivery'];
        $response = $this->post(route('checkout.store'), $data);
        $order = Order::with('items', 'statusHistory')->sole();
        $response->assertRedirect(route('checkout.confirmation', $order->order_number));
        $this->assertSame(55006, $order->confirmed_total);
        $this->assertSame(55006, $order->subtotal);
        $this->assertNull($order->user_id);
        $this->assertSame('received', $order->status->value);
        $this->assertSame('pickup', $order->fulfillment_method->value);
        $this->assertTrue($order->has_items_to_confirm);
        $this->assertSame('5.500', $order->items[0]->quantity);
        $this->assertSame(10001, $order->items[0]->unit_price);
        $this->assertNull($order->items[1]->unit_price);
        $this->assertNull($order->items[1]->subtotal);
        $this->assertTrue($order->items[1]->requires_confirmation);
        $this->assertSame('received', $order->statusHistory->sole()->new_status->value);
        $this->assertNull($order->statusHistory->sole()->previous_status);
        $this->assertNull($order->statusHistory->sole()->changed_by);
        $this->assertSame('20.000', $p->fresh()->stock);
        $this->assertEmpty(session('cart', []));
        $this->post(route('checkout.store'), $data)->assertRedirect($response->headers->get('Location'));
        $this->assertDatabaseCount('orders', 1);
        $p->update(['name' => 'Nombre cambiado', 'price' => 1]);
        $this->get($response->headers->get('Location'))->assertOk()->assertSee('Cable &amp; cobre', false)->assertSee('WhatsApp todavía no está configurado');
        $this->flushSession();
        $this->get($response->headers->get('Location'))->assertNotFound();
    }

    public function test_registered_contact_is_snapshot_and_numbers_are_unique(): void
    {
        $user = User::factory()->create(['phone' => '1155556666']);
        $this->actingAs($user);
        $p = $this->product();
        for ($i = 0; $i < 2; $i++) {
            $this->add($p);
            $this->get(route('checkout.create'))->assertSee($user->first_name)->assertSee('1155556666');
            $this->post(route('checkout.store'), $this->contact())->assertRedirect();
        }
        $this->assertDatabaseCount('orders', 2);
        $this->assertCount(2, Order::pluck('order_number')->unique());
        $this->assertSame($user->id, Order::first()->user_id);
        $this->assertSame($user->id, Order::first()->statusHistory->sole()->changed_by);
        $this->assertSame('Ana', Order::first()->customer_first_name);
        $this->assertSame('1155556666', $user->fresh()->phone);
    }

    public function test_empty_checkout_and_invalid_contact_or_token_do_not_create_orders(): void
    {
        $this->get(route('checkout.create'))->assertRedirect(route('cart.index'));
        $this->add($this->product());
        $this->get(route('checkout.create'));
        $this->post(route('checkout.store'), ['token' => session('checkout.token')])->assertSessionHasErrors(['first_name', 'last_name', 'phone']);
        $this->post(route('checkout.store'), array_replace($this->contact(), ['token' => str_repeat('x', 64)]))->assertSessionHasErrors();
        $this->assertDatabaseCount('orders', 0);
        $this->assertNotEmpty(session('cart'));
    }

    public function test_price_changes_require_new_review_then_use_current_price(): void
    {
        $p = $this->product();
        $this->add($p);
        $this->get(route('checkout.create'));
        $data = $this->contact();
        $p->update(['price' => 20002]);
        $this->post(route('checkout.store'), $data)->assertSessionHasErrors('cart');
        $this->assertDatabaseCount('orders', 0);
        $this->get(route('checkout.create'))->assertSee('$ 200,02');
        $this->post(route('checkout.store'), $this->contact())->assertRedirect();
        $this->assertSame(20002, Order::sole()->confirmed_total);
    }

    public static function changedProducts(): array
    {
        return [['stock', '0'], ['status', 'draft'], ['status', 'private']];
    }

    #[DataProvider('changedProducts')]
    public function test_checkout_revalidates_and_preserves_cart(string $field, string $value): void
    {
        $p = $this->product();
        $this->add($p);
        $this->get(route('checkout.create'));
        $p->update([$field => $value]);
        $this->post(route('checkout.store'), $this->contact())->assertSessionHasErrors();
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertNotEmpty(session('cart'));
        $this->get(route('cart.index'))->assertOk()->assertSee('Eliminar');
    }

    public function test_variant_snapshot_and_deactivation(): void
    {
        $p = Product::factory()->variable()->create(['status' => 'published']);
        $v = ProductVariant::factory()->create(['product_id' => $p->id, 'name' => '4 L', 'active' => true, 'stock' => '10', 'price' => 2700000]);
        $this->add($p, '1', $v->id);
        $this->get(route('checkout.create'));
        $v->update(['active' => false]);
        $this->post(route('checkout.store'), $this->contact())->assertSessionHasErrors();
        $this->assertDatabaseCount('orders', 0);
        $v->update(['active' => true]);
        $this->get(route('checkout.create'));
        $this->post(route('checkout.store'), $this->contact());
        $item = OrderItem::sole();
        $this->assertSame($v->id, $item->product_variant_id);
        $this->assertSame('4 L', $item->variant_name);
        $this->assertSame(2700000, $item->subtotal);
    }

    public function test_item_failure_rolls_back_entire_order(): void
    {
        $this->add($this->product());
        $this->get(route('checkout.create'));
        OrderItem::creating(function (): void {
            throw new \RuntimeException('Simulated write failure');
        });
        $this->withoutExceptionHandling();
        try {
            $this->post(route('checkout.store'), $this->contact());
            $this->fail('Expected write failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('Simulated write failure', $e->getMessage());
        } finally {
            OrderItem::flushEventListeners();
        }
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('order_status_history', 0);
        $this->assertNotEmpty(session('cart'));
    }

    public function test_updates_and_accumulation_cannot_exceed_stock(): void
    {
        $p = $this->product(['stock' => '2']);
        $this->add($p, '2');
        $this->add($p)->assertSessionHasErrors();
        $this->patch(route('cart.update', $p->id.'-0'), ['quantity' => '3'])->assertSessionHasErrors();
        $this->assertSame('2.000', session('cart.'.$p->id.'-0.quantity'));
    }

    public function test_monetary_overflow_and_domain_limit_are_rejected(): void
    {
        $p = $this->product(['price' => 999999999999999, 'stock' => null]);
        $this->add($p, '9999')->assertSessionHasErrors();
        $this->add($p, '2')->assertSessionHasErrors();
        $this->assertEmpty(session('cart', []));
        $this->add($p);
        $this->add($this->product());
        $this->get(route('checkout.create'))->assertRedirect(route('cart.index'))->assertSessionHasErrors();
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_unknown_stock_and_missing_price_are_handled_without_invented_price(): void
    {
        $this->add($this->product(['stock' => null, 'price' => null]));
        $this->get(route('checkout.create'))->assertOk()->assertSee('A confirmar');
        $this->post(route('checkout.store'), $this->contact())->assertRedirect();
        $this->assertSame(0, Order::sole()->confirmed_total);
        $this->assertTrue(OrderItem::sole()->requires_confirmation);
        $this->assertNull(OrderItem::sole()->unit_price);
    }

    public function test_stale_review_and_wrong_phone_are_rejected(): void
    {
        $this->add($this->product());
        $this->get(route('checkout.create'));
        $stale = $this->contact();
        $this->get(route('checkout.create'));
        $this->post(route('checkout.store'), $stale)->assertSessionHasErrors('cart');
        $this->post(route('checkout.store'), array_replace($this->contact(), ['phone' => '------']))->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_committed_order_recovers_after_session_completion_was_not_saved(): void
    {
        $this->add($this->product());
        $this->get(route('checkout.create'));
        $review = session('checkout');
        $cart = session('cart');
        $data = $this->contact();
        $this->post(route('checkout.store'), $data)->assertRedirect();
        $this->withSession(['checkout' => $review, 'cart' => $cart, 'checkout_completed' => []]);
        $this->post(route('checkout.store'), $data)->assertRedirect();
        $this->assertDatabaseCount('orders', 1);
        $this->assertEmpty(session('cart', []));
    }

    public function test_cart_only_stores_ids_and_quantity_and_enforces_line_limit(): void
    {
        $p = $this->product();
        $this->add($p);
        $this->assertSame(['product_id' => $p->id, 'product_variant_id' => null, 'quantity' => '1.000'], session('cart.'.$p->id.'-0'));
        $lines = [];
        for ($i = 100; $i < 150; $i++) {
            $lines[$i.'-0'] = ['product_id' => $i, 'product_variant_id' => null, 'quantity' => '1.000'];
        }
        $this->withSession(['cart' => $lines]);
        $this->add($p)->assertSessionHasErrors('cart');
        $this->assertCount(50, session('cart'));
    }

    public function test_confirmation_is_not_available_to_another_registered_user(): void
    {
        $this->actingAs(User::factory()->create());
        $this->add($this->product());
        $this->get(route('checkout.create'));
        $this->post(route('checkout.store'), $this->contact());
        $order = Order::sole();
        $other = User::factory()->create();
        $this->actingAs($other)->withSession(['password_hash_web' => $other->getAuthPassword()]);
        $this->get(route('checkout.confirmation', $order->order_number))->assertNotFound();
    }

    public function test_whatsapp_is_configured_encoded_and_uses_snapshots(): void
    {
        $this->add($this->product(['name' => 'Cable & cobre', 'sale_unit' => 'meter']), '5.5');
        $this->add($this->product(['purchase_mode' => 'inquiry']));
        $this->get(route('checkout.create'));
        $this->post(route('checkout.store'), $this->contact());
        $order = Order::sole();
        $wa = app(WhatsAppOrder::class);
        $this->assertNull($wa->url($order));
        config(['shop.whatsapp_number' => '5491155551234']);
        $message = $wa->message($order);
        $url = $wa->url($order);
        $this->assertSame('https://wa.me/5491155551234?text='.rawurlencode($message), $url);
        $this->assertStringContainsString('5.5 Metro x Cable & cobre', $message);
        $this->assertStringContainsString('A confirmar', $message);
        $this->assertStringContainsString('Productos a confirmar: 1', $message);
        $this->assertStringContainsString('Retiro en local.', $message);
        $this->assertStringContainsString($order->order_number, $message);
        config(['shop.whatsapp_number' => 'https://evil.test']);
        $this->assertNull($wa->url($order));
    }
}
