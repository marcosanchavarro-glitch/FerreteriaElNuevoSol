<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Services\Storefront\WhatsAppOrder;
use Database\Seeders\RenderDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DemoModeTest extends TestCase
{
    use RefreshDatabase;

    private function demo(): void
    {
        config(['demo.enabled' => true, 'demo.access_hash' => Hash::make('Example-Demo-Gate-For-Tests')]);
    }

    private function enter(): void
    {
        $this->post('/acceso-demo', ['password' => 'Example-Demo-Gate-For-Tests'])->assertRedirect('/');
    }

    public function test_demo_gate_protects_navigation_auth_registration_and_writes(): void
    {
        $this->demo();
        foreach (['/', '/productos', '/login', '/registro', '/admin', '/carrito', '/checkout'] as $url) {
            $this->get($url)->assertRedirect('/acceso-demo')->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        }
        $this->post('/registro', [])->assertRedirect('/acceso-demo');
        $this->postJson('/carrito', [])->assertForbidden();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_invalid_password_is_not_flashed_and_attempts_are_limited(): void
    {
        $this->demo();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/acceso-demo', ['password' => 'wrong'])->assertSessionHasErrors('password')->assertSessionMissing('_old_input.password');
        }
        $this->post('/acceso-demo', ['password' => 'wrong'])->assertStatus(429);
    }

    public function test_public_demo_allows_guests_but_keeps_admin_authentication_and_roles(): void
    {
        config(['demo.enabled' => true, 'demo.access_required' => false, 'demo.access_hash' => '']);
        foreach (['/', '/productos', '/login', '/registro', '/carrito'] as $url) {
            $this->get($url)->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        }
        $this->get('/')->assertSee('Sitio de demostración')->assertDontSee('Demostración privada');
        $this->get('/acceso-demo')->assertRedirect('/');
        $this->post('/acceso-demo')->assertRedirect('/');
        $this->get('/admin')->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => 'customer']))->get('/admin')->assertForbidden();
    }

    public function test_public_demo_checkout_remains_fictitious(): void
    {
        config(['demo.access_required' => false]);
        $this->test_fictitious_checkout_persists_without_whatsapp_or_stock_changes();
    }

    public function test_gate_keeps_user_roles_separate_and_survives_navigation(): void
    {
        $this->demo();
        $this->enter();
        $this->assertGuest();
        $this->get('/')->assertOk()->assertSee('Demostración privada');
        $this->get('/productos')->assertOk();
        $this->get('/admin')->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => 'customer']))->get('/admin')->assertForbidden();
        $this->flushSession();
        $this->enter();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get('/admin')->assertOk();
        $this->assertSame(10080, config('session.lifetime'));
    }

    public function test_gate_rotation_revokes_existing_access_and_missing_config_fails_closed(): void
    {
        $this->demo();
        $this->enter();
        config(['demo.access_hash' => Hash::make('A-New-Private-Test-Password')]);
        $this->get('/productos')->assertRedirect('/acceso-demo');
        config(['demo.access_hash' => '']);
        $this->get('/')->assertStatus(503);
    }

    public function test_disabled_demo_keeps_normal_site_and_whatsapp(): void
    {
        config(['demo.enabled' => false, 'shop.whatsapp_number' => '5491155550000']);
        $this->get('/')->assertOk()->assertDontSee('Demostración privada');
        $this->get('/acceso-demo')->assertNotFound();
        $this->assertStringContainsString('https://wa.me/5491155550000', app(WhatsAppOrder::class)->url(Order::factory()->create()));
    }

    public function test_fictitious_checkout_persists_without_whatsapp_or_stock_changes(): void
    {
        $this->demo();
        $this->enter();
        Setting::create(['key' => 'whatsapp_number', 'value' => '5491155550000']);
        $product = Product::factory()->create(['status' => 'published', 'price' => 10001, 'stock' => '25.500', 'sale_unit' => 'meter']);
        $this->post('/carrito', ['product_id' => $product->id, 'quantity' => '1.125'])->assertSessionHasNoErrors();
        $this->get('/checkout')->assertOk();
        $response = $this->post('/checkout', ['first_name' => 'Cliente', 'last_name' => 'Ficticio', 'phone' => '1155550000', 'token' => session('checkout.token')]);
        $order = Order::sole();
        $this->assertSame(11251, $order->confirmed_total);
        $this->assertSame('1.125', $order->items->sole()->quantity);
        $this->assertSame('25.500', $product->fresh()->stock);
        $this->assertNull(app(WhatsAppOrder::class)->url($order));
        $this->get($response->headers->get('Location'))->assertOk()->assertSee('Pedido de prueba creado')->assertDontSee('https://wa.me/');
    }

    public function test_demo_recovery_does_not_create_mail_tokens(): void
    {
        $this->demo();
        $this->enter();
        $user = User::factory()->create();
        $this->post('/recuperar-contrasena', ['email' => $user->email])->assertSessionHas('status');
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_demo_cancels_mail_delivery_even_when_a_mailer_is_explicitly_selected(): void
    {
        $this->demo();
        (new AppServiceProvider($this->app))->boot();
        $mailer = Mail::mailer('array');
        $mailer->raw('Pedido ficticio', fn ($message) => $message->to('customer@demo.test'));
        $this->assertCount(0, $mailer->getSymfonyTransport()->messages());
        $this->assertSame('array', config('mail.default'));
    }

    public function test_seed_is_idempotent_and_does_not_overwrite_edits_or_passwords(): void
    {
        $this->demo();
        foreach (['admin', 'seller', 'customer'] as $role) {
            config(['demo.users.'.$role => ['email' => $role.'@demo.test', 'password' => 'Private-Test-Password-'.$role]]);
        }
        $this->seed(RenderDemoSeeder::class);
        $product = Product::firstOrFail();
        $product->update(['name' => 'Edición conservada']);
        $admin = User::where('role', 'admin')->sole();
        $admin->update(['password' => 'A-New-Test-Password']);
        $this->seed(RenderDemoSeeder::class);
        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('products', 4);
        $this->assertSame('Edición conservada', $product->fresh()->name);
        $this->assertTrue(Hash::check('A-New-Test-Password', $admin->fresh()->password));
    }

    public function test_seed_refuses_existing_non_demo_database(): void
    {
        $this->demo();
        User::factory()->create();
        $this->expectException(\LogicException::class);
        $this->seed(RenderDemoSeeder::class);
    }
}
