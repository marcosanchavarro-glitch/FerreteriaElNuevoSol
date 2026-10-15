<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Services\Content\SiteSettings;
use App\Services\Storefront\WhatsAppOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function data(array $changes = []): array
    {
        return array_merge(['business_name' => 'El Nuevo Sol', 'phone' => '011 5555 0000', 'whatsapp_number' => '5491155550000', 'pickup_address' => 'Dirección de prueba 123', 'pickup_hours' => 'Lunes a viernes'], $changes);
    }

    public function test_admin_can_save_only_allowed_data_used_by_home_pickup_and_whatsapp(): void
    {
        Setting::create(['key' => 'APP_KEY', 'value' => 'SECRET-NEVER-DISPLAY']);
        $this->actingAs(User::factory()->admin()->create())->get('/admin/settings')->assertOk()->assertDontSee('SECRET-NEVER-DISPLAY');
        $this->put('/admin/settings', $this->data())->assertRedirect()->assertSessionHasNoErrors();
        $this->get('/')->assertSee('Dirección de prueba 123')->assertSee('Lunes a viernes')->assertSee('011 5555 0000');
        $order = Order::factory()->guest()->create();
        $this->assertStringStartsWith('https://wa.me/5491155550000?', app(WhatsAppOrder::class)->url($order));
        $this->put('/admin/settings', $this->data(['whatsapp_number' => '', 'pickup_address' => 'Otra dirección']))->assertSessionHasNoErrors();
        $this->assertNull(app(WhatsAppOrder::class)->url($order));
        $this->get('/')->assertSee('Otra dirección')->assertDontSee('Dirección de prueba 123');
    }

    public function test_settings_permission_on_get_and_write(): void
    {
        $this->get('/admin/settings')->assertRedirect('/login');
        $this->put('/admin/settings', $this->data())->assertRedirect('/login');
        foreach (['seller', 'customer'] as $role) {
            $this->flushSession();
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->get('/admin/settings')->assertForbidden();
            $this->put('/admin/settings', $this->data())->assertForbidden();
        }
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_arbitrary_keys_and_invalid_whatsapp_rejected_atomically(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        foreach ([['APP_KEY' => 'hacked'], ['settings' => ['database' => 'hacked']], ['whatsapp_number' => 'javascript:alert(1)']] as $extra) {
            $this->put('/admin/settings', $this->data($extra))->assertSessionHasErrors();
            $this->assertDatabaseCount('settings', 0);
        }
    }

    public function test_env_fallback_and_plain_text_output(): void
    {
        config(['shop.whatsapp_number' => '5491112345678']);
        $this->assertSame('5491112345678', app(SiteSettings::class)->get('whatsapp_number'));
        $this->actingAs(User::factory()->admin()->create())->put('/admin/settings', $this->data(['pickup_address' => '<script>alert(1)</script>']))->assertSessionHasNoErrors();
        $this->get('/')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }
}
