<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\Product;
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

    public function test_independent_whatsapp_contacts_temporary_labels_and_confirmed_location(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->put('/admin/settings', $this->data([
            'whatsapp_number_kind' => 'temporary', 'whatsapp_inquiries_number' => '5491155552222', 'whatsapp_inquiries_kind' => 'business',
            'locality' => 'Localidad ficticia', 'province' => 'Provincia ficticia', 'maps_url' => 'https://maps.app.goo.gl/FictitiousLab',
        ]))->assertSessionHasNoErrors();
        $settings = app(SiteSettings::class);
        $this->assertSame('temporary', $settings->get('whatsapp_number_kind'));
        $audit = ActivityLog::where('module', 'settings')->latest('id')->firstOrFail();
        $this->assertSame('temporary', $audit->after_values['whatsapp_number_kind']);
        $this->assertSame('https://maps.app.goo.gl/FictitiousLab', $audit->after_values['maps_url']);
        $this->assertStringStartsWith('https://wa.me/5491155552222?text=', $settings->inquiryUrl());
        $this->assertStringStartsWith('https://wa.me/5491155550000?text=', app(WhatsAppOrder::class)->url(Order::factory()->guest()->create()));
        $this->get('/')->assertSee('Localidad ficticia')->assertSee('Provincia ficticia')->assertSee('https://maps.app.goo.gl/FictitiousLab')->assertSee('Consultar por WhatsApp');
        config(['demo.enabled' => true, 'demo.access_required' => false]);
        $this->assertNull($settings->inquiryUrl());
        $this->get('/')->assertDontSee('https://wa.me/');
    }

    public function test_unsafe_maps_and_invalid_contact_settings_are_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        foreach (['javascript:alert(1)', 'https://google.com.evil.test/maps/a', 'https://google.com/url?q=https://evil.test', 'http://maps.google.com/maps', 'https://user:pass@google.com/maps/a', 'https://www.google.com/maps/embed/v1/place?key=private', 'https://www.google.com/maps?TOKEN=private'] as $url) {
            $this->put('/admin/settings', $this->data(['maps_url' => $url]))->assertSessionHasErrors('maps_url');
        }
        $this->put('/admin/settings', $this->data(['whatsapp_inquiries_number' => '+54 11 5555 2222']))->assertSessionHasErrors('whatsapp_inquiries_number');
        $this->put('/admin/settings', $this->data(['whatsapp_number_kind' => 'invented']))->assertSessionHasErrors('whatsapp_number_kind');
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_missing_location_and_inquiry_contact_do_not_invent_public_details(): void
    {
        $this->get('/')->assertOk()->assertDontSee('Cómo llegar')->assertDontSee('Consultar por WhatsApp')->assertDontSee('https://maps.');
    }

    public function test_contacts_can_be_selected_independently_and_follow_agenda_updates(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $agenda = ['contact_primary_number' => '5491155550001', 'contact_secondary_number' => '5491155550002', 'contact_tertiary_number' => '5491155550003', 'whatsapp_orders_contact' => 'primary', 'whatsapp_inquiries_contact' => 'tertiary'];
        $this->put('/admin/settings', $this->data($agenda))->assertSessionHasNoErrors();
        $settings = app(SiteSettings::class);
        $this->assertSame('5491155550001', $settings->get('whatsapp_number'));
        $this->assertStringStartsWith('https://wa.me/5491155550003?', $settings->inquiryUrl());
        $this->put('/admin/settings', $this->data(['contact_primary_number' => '5491155550004']))->assertSessionHasNoErrors();
        $this->assertSame('5491155550004', $settings->get('whatsapp_number'));
        $this->put('/admin/settings', $this->data(['contact_primary_number' => '']))->assertSessionHasErrors('whatsapp_orders_contact');
        $this->assertSame('5491155550004', $settings->get('whatsapp_number'));
        $this->put('/admin/settings', $this->data(['whatsapp_orders_contact' => 'unrecognized']))->assertSessionHasErrors('whatsapp_orders_contact');
    }

    public function test_contact_location_is_shared_with_footer_and_checkout_and_map_is_not_loaded_initially(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $data = $this->data(['locality' => 'Localidad ficticia', 'province' => 'Provincia ficticia', 'postal_code' => 'C1000TEST',
            'maps_url' => 'https://maps.app.goo.gl/FictitiousLab', 'maps_embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!2m3!1f0']);
        $this->put('/admin/settings', $data)->assertSessionHasNoErrors();
        $this->get(route('store.contact'))->assertOk()->assertSee('Localidad ficticia')->assertSee('Provincia ficticia')->assertSee('C1000TEST')
            ->assertSee('Cargar mapa de Google Maps')->assertSee('Abrir ubicación en Google Maps')->assertDontSee('<iframe', false);
        $this->get('/')->assertSee('C1000TEST')->assertSee(route('store.contact'));
        $product = Product::factory()->create(['status' => 'published', 'price' => 100, 'stock' => '10.000']);
        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => '1'])->assertRedirect();
        $this->get(route('checkout.create'))->assertOk()->assertSee('Dirección de prueba 123')->assertSee('C1000TEST');
        $audit = ActivityLog::where('module', 'settings')->latest('id')->firstOrFail();
        $this->assertSame('C1000TEST', $audit->after_values['postal_code']);
        $this->assertSame($data['maps_embed_url'], $audit->after_values['maps_embed_url']);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_empty_or_partial_contact_configuration_has_no_invented_map(): void
    {
        $this->get(route('store.contact'))->assertOk()->assertSee('pendiente de completar')->assertDontSee('data-map-embed', false)->assertDontSee('Cómo llegar');
        $this->actingAs(User::factory()->admin()->create());
        $this->put('/admin/settings', $this->data(['postal_code' => '1000']))->assertSessionHasNoErrors();
        $this->get(route('store.contact'))->assertOk()->assertSee('Dirección de prueba 123')->assertSee('pendiente de completar')->assertDontSee('data-map-embed', false);
        $this->put('/admin/settings', $this->data(['maps_embed_url' => 'https://www.google.com/maps/embed?pb=!1m18']))->assertSessionHasErrors(['locality', 'province', 'maps_url']);
        $this->assertSame('', app(SiteSettings::class)->get('maps_embed_url'));
    }

    public function test_embed_rejects_html_api_keys_unsafe_origins_and_invalid_postal_codes(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        foreach (['<iframe src="https://www.google.com/maps/embed?pb=!1m18"></iframe>', 'https://www.google.com/maps/embed/v1/place?key=private&q=test',
            'https://www.google.com.evil.test/maps/embed?pb=!1m18', 'https://user:pass@www.google.com/maps/embed?pb=!1m18',
            'http://www.google.com/maps/embed?pb=!1m18', 'https://www.google.com/maps/embed?pb=!1m18&key=private', 'https://www.google.com/maps/embed?pb=!1m18#fragment'] as $url) {
            $this->put('/admin/settings', $this->data(['locality' => 'Ficticia', 'province' => 'Ficticia', 'maps_url' => 'https://maps.app.goo.gl/Test', 'maps_embed_url' => $url]))->assertSessionHasErrors('maps_embed_url');
        }
        foreach (['<script>', str_repeat('1', 21)] as $postal) {
            $this->put('/admin/settings', $this->data(['postal_code' => $postal]))->assertSessionHasErrors('postal_code');
        }
        $this->assertDatabaseCount('settings', 0);
    }
}
