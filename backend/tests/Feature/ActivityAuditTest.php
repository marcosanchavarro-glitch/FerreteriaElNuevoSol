<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Banner;
use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Audit\ActivityLogService;
use App\Services\Catalog\CategoryImages;
use App\Services\Catalog\ProductGallery;
use App\Services\Content\ContentManager;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ActivityAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('catalog');
    }

    private function productData(array $extra = []): array
    {
        return array_replace(['name' => 'Taladro', 'slug' => 'taladro', 'product_type' => 'simple', 'sale_unit' => 'unit', 'purchase_mode' => 'inquiry', 'status' => 'draft', 'featured' => 0], $extra);
    }

    private function sectionData(array $extra = []): array
    {
        return array_replace(['type' => 'hero', 'title' => 'Portada', 'active' => 1, 'sort_order' => 0, 'max_items' => 6], $extra);
    }

    public function test_product_creation_and_update_capture_actor_time_and_only_changed_fields(): void
    {
        $this->freezeTime();
        $actor = User::factory()->admin()->create();
        $this->actingAs($actor);
        $this->post('/admin/products', $this->productData())->assertSessionHasNoErrors();
        $product = Product::sole();
        $log = ActivityLog::sole();
        $this->assertSame('created', $log->action);
        $this->assertSame($actor->id, $log->actor_id);
        $this->assertSame('admin', $log->actor_role);
        $this->assertSame(now()->format('Y-m-d H:i:s'), $log->created_at->format('Y-m-d H:i:s'));
        $this->put('/admin/products/'.$product->id, $this->productData(['status' => 'published', 'featured' => 1]))->assertSessionHasNoErrors();
        $log = ActivityLog::latest('id')->first();
        $this->assertSame(['status' => 'draft', 'featured' => false], $log->before_values);
        $this->assertSame(['status' => 'published', 'featured' => true], $log->after_values);
        $this->assertDatabaseCount('activity_logs', 2);
        $this->put('/admin/products/'.$product->id, $this->productData(['status' => 'published', 'featured' => 1]))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('activity_logs', 2);
    }

    public function test_variants_capture_creation_and_presentation_activation_changes(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $product = Product::factory()->variable()->create();
        $base = '/admin/products/'.$product->id.'/variants';
        $data = ['name' => 'Pintura', 'value' => '4 L', 'active' => 1, 'purchase_mode' => 'inquiry'];
        $this->post($base, $data)->assertSessionHasNoErrors();
        $variant = ProductVariant::sole();
        $this->put($base.'/'.$variant->id, array_replace($data, ['value' => '8 L', 'active' => 0]))->assertSessionHasNoErrors();
        $log = ActivityLog::latest('id')->first();
        $this->assertSame('variants', $log->module);
        $this->assertSame(['value' => '4 L', 'active' => true], $log->before_values);
        $this->assertSame(['value' => '8 L', 'active' => false], $log->after_values);
        $this->assertDatabaseCount('activity_logs', 2);
    }

    public function test_categories_hierarchy_and_product_assignment_are_audited(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $parent = Category::factory()->create();
        $data = ['name' => 'Hija', 'slug' => 'hija', 'active' => 1, 'featured' => 0, 'sort_order' => 0];
        $this->post('/admin/categories', $data)->assertSessionHasNoErrors();
        $category = Category::where('slug', 'hija')->sole();
        $this->put('/admin/categories/'.$category->id, $data + ['parent_id' => $parent->id])->assertSessionHasNoErrors();
        $this->assertSame(['parent_id' => $parent->id], ActivityLog::latest('id')->first()->after_values);
        $product = Product::factory()->create();
        $this->put('/admin/products/'.$product->id.'/categories', ['category_ids' => [$category->id]])->assertSessionHasNoErrors();
        $this->assertSame(['category_ids' => [$category->id]], ActivityLog::latest('id')->first()->after_values);
        $this->assertDatabaseCount('activity_logs', 3);
    }

    public function test_gallery_upload_primary_reorder_delete_each_produce_one_event(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $product = Product::factory()->create();
        $gallery = app(ProductGallery::class);
        $one = $gallery->upload($product, UploadedFile::fake()->image('one.jpg'), 'Uno');
        $two = $gallery->upload($product, UploadedFile::fake()->image('two.png'), 'Dos');
        $gallery->primary($product, $two);
        $gallery->move($product, $two, 'up');
        $gallery->delete($product, $one);
        $this->assertSame(['uploaded', 'uploaded', 'primary', 'reordered', 'deleted'], ActivityLog::orderBy('id')->pluck('action')->all());
        $last = ActivityLog::latest('id')->first();
        $this->assertCount(2, $last->before_values['images']);
        $this->assertCount(1, $last->after_values['images']);
    }

    public function test_category_image_and_cms_image_changes_are_audited(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $category = Category::factory()->create(['image_path' => null]);
        app(CategoryImages::class)->replace($category, UploadedFile::fake()->image('c.jpg'));
        $this->assertArrayHasKey('image_path', ActivityLog::sole()->after_values);
        $section = new HomepageSection;
        app(ContentManager::class)->save($section, ['type' => 'hero', 'title' => 'Portada', 'active' => true, 'sort_order' => 0], UploadedFile::fake()->image('hero.jpg'));
        $this->assertSame('created', ActivityLog::latest('id')->first()->action);
        $this->assertDatabaseCount('activity_logs', 2);
        app(ContentManager::class)->removeImage($section);
        $this->assertSame(['image_path' => null], ActivityLog::latest('id')->first()->after_values);
    }

    public function test_cms_content_visibility_and_order_are_audited(): void
    {
        $this->actingAs(User::factory()->seller()->create());
        $this->post('/admin/content/sections', $this->sectionData())->assertSessionHasNoErrors();
        $section = HomepageSection::sole();
        $this->put('/admin/content/sections/'.$section->id, $this->sectionData(['title' => 'Nueva portada']))->assertSessionHasNoErrors();
        $this->assertSame(['title' => 'Portada'], ActivityLog::latest('id')->first()->before_values);
        $this->patch('/admin/content/sections/'.$section->id.'/visibility', ['active' => 0, 'confirm' => 1])->assertSessionHasNoErrors();
        $second = HomepageSection::factory()->create(['sort_order' => 1]);
        $this->post('/admin/content/sections/'.$second->id.'/move', ['direction' => 'up'])->assertSessionHasNoErrors();
        $this->assertSame(['order' => [$second->id, $section->id]], ActivityLog::latest('id')->first()->after_values);
        $this->assertDatabaseCount('activity_logs', 4);
        $banner = Banner::factory()->create(['title' => 'Antes']);
        $this->put('/admin/content/banners/'.$banner->id, ['title' => 'Después', 'active' => 1, 'sort_order' => 0])->assertSessionHasNoErrors();
        $this->assertSame('banners', ActivityLog::latest('id')->first()->module);
    }

    public function test_order_status_audit_is_minimal_and_consistent_with_history(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $order = Order::factory()->create();
        $this->patch('/admin/orders/'.$order->id.'/status', ['expected_status' => 'received', 'status' => 'preparing'])->assertSessionHasNoErrors();
        $this->assertSame(['status' => 'received'], ActivityLog::sole()->before_values);
        $this->assertSame(['status' => 'preparing'], ActivityLog::sole()->after_values);
        $this->assertCount(1, $order->statusHistory);
        $this->assertStringNotContainsString($order->customer_phone, ActivityLog::sole()->toJson());
        $this->patch('/admin/orders/'.$order->id.'/status', ['expected_status' => 'received', 'status' => 'cancelled', 'confirm_cancel' => 1])->assertSessionHasErrors();
        $this->assertDatabaseCount('activity_logs', 1);
    }

    public function test_settings_are_grouped_and_secrets_and_personal_data_are_discarded(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->put('/admin/settings', ['business_name' => 'Ferretería', 'whatsapp_number' => '5491155550000', 'pickup_hours' => '9 a 18'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('activity_logs', 1);
        $this->assertSame('settings', ActivityLog::sole()->module);
        $audit = app(ActivityLogService::class);
        $log = DB::transaction(fn () => $audit->record('products', 'updated', 1, ['status' => 'draft', 'password' => 'SECRET'], ['status' => 'published', 'token' => 'SECRET', 'email' => 'SECRET', 'settings' => ['api_key' => 'SECRET']]));
        $this->assertSame(['status' => 'published'], $log->after_values);
        $this->assertStringNotContainsString('SECRET', $log->toJson());
        $safe = $audit->sanitize('images', ['images' => [['id' => 1, 'path' => 'safe.webp', 'password' => 'SECRET', 'alt_text' => ['token' => 'SECRET']]]]);
        $this->assertSame(['images' => [['id' => 1, 'path' => 'safe.webp']]], $safe);
    }

    public function test_failed_audit_rolls_back_product_and_order_with_history(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $product = Product::factory()->create(['name' => 'Original']);
        $order = Order::factory()->create();
        ActivityLog::creating(fn () => throw new \RuntimeException('Simulated audit failure'));
        $this->put('/admin/products/'.$product->id, $this->productData())->assertStatus(500);
        $this->assertSame('Original', $product->fresh()->name);
        $this->patch('/admin/orders/'.$order->id.'/status', ['expected_status' => 'received', 'status' => 'preparing'])->assertStatus(500);
        $this->assertSame('received', $order->fresh()->status->value);
        $this->assertDatabaseCount('order_status_history', 0);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_failed_audit_restores_image_file_and_database(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $product = Product::factory()->create();
        $gallery = app(ProductGallery::class);
        $image = $gallery->upload($product, UploadedFile::fake()->image('one.jpg'), null);
        ActivityLog::creating(fn () => throw new \RuntimeException('Simulated failure'));
        try {
            $gallery->delete($product, $image);
            $this->fail('Expected rollback');
        } catch (\RuntimeException $e) {
            $this->assertSame('Simulated failure', $e->getMessage());
        }
        $this->assertModelExists($image);
        Storage::disk('catalog')->assertExists($image->path);
        $this->assertDatabaseCount('activity_logs', 1);
    }

    public static function restrictedRoles(): array
    {
        return [['seller'], ['customer']];
    }

    #[DataProvider('restrictedRoles')]
    public function test_non_admin_cannot_read_global_history(string $role): void
    {
        $log = ActivityLog::factory()->create();
        $this->actingAs(User::factory()->create(['role' => $role]));
        $this->get('/admin/activity-logs')->assertForbidden();
        $this->get('/admin/activity-logs/'.$log->id)->assertForbidden();
        if ($role === 'seller') {
            $this->get('/admin')->assertDontSee('Historial de actividad');
        }
    }

    public function test_guest_is_redirected_and_no_mutation_routes_exist(): void
    {
        $log = ActivityLog::factory()->create();
        $this->get('/admin/activity-logs')->assertRedirect('/login');
        $this->actingAs(User::factory()->admin()->create());
        foreach (['post', 'put', 'patch', 'delete'] as $method) {
            $this->$method('/admin/activity-logs/'.$log->id, ['action' => 'fake'])->assertStatus(405);
        }
        $this->post('/admin/activity-logs', [])->assertStatus(405);
        $this->assertSame('test.created', $log->fresh()->action);
    }

    public function test_filters_pagination_detail_and_escaped_text(): void
    {
        $actor = User::factory()->admin()->create(['first_name' => '<script>alert(1)</script>']);
        $this->actingAs($actor);
        $audit = app(ActivityLogService::class);
        for ($i = 0; $i < 25; $i++) {
            DB::transaction(fn () => $audit->record('products', 'updated', $i, ['name' => 'Antes'], ['name' => 'Después']));
        }
        $log = ActivityLog::latest('id')->first();
        $this->get('/admin/activity-logs')->assertOk()->assertViewHas('logs', fn ($logs) => $logs->count() === 20 && $logs->total() === 25)->assertDontSee('<script>alert(1)</script>', false);
        $this->get('/admin/activity-logs?'.http_build_query(['actor' => $actor->id, 'module' => 'products', 'action' => 'updated', 'entity' => '24', 'result' => 'success', 'from' => now()->subDay()->format('Y-m-d'), 'to' => now()->addDay()->format('Y-m-d'), 'q' => 'alert']))->assertOk()->assertViewHas('logs', fn ($logs) => $logs->total() === 1);
        $this->get('/admin/activity-logs?q=%27%20OR%201%3D1')->assertOk()->assertViewHas('logs', fn ($logs) => $logs->total() === 0);
        $this->get('/admin/activity-logs/'.$log->id)->assertOk()->assertSee('Antes')->assertSee('Después')->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_actor_history_survives_deactivation_rename_and_deletion(): void
    {
        $actor = User::factory()->seller()->create(['first_name' => 'Histórico', 'last_name' => 'Vendedor']);
        $log = DB::transaction(fn () => app(ActivityLogService::class)->record('products', 'updated', 1, ['name' => 'A'], ['name' => 'B'], $actor));
        $actor->forceFill(['active' => false, 'first_name' => 'Otro', 'role' => 'customer'])->save();
        $this->assertSame('Histórico Vendedor', $log->fresh()->actor_name);
        $this->assertSame('seller', $log->fresh()->actor_role);
        $actor->delete();
        $this->assertNull($log->fresh()->user_id);
        $this->assertSame($actor->id, $log->fresh()->actor_id);
        $this->actingAs(User::factory()->admin()->create())->get('/admin/activity-logs/'.$log->id)->assertOk()->assertSee('Histórico Vendedor');
    }

    public function test_rejected_staff_write_does_not_copy_request_or_claim_success(): void
    {
        $this->actingAs(User::factory()->seller()->create());
        $this->put('/admin/settings', ['business_name' => 'Forbidden', 'token' => 'SECRET'])->assertForbidden();
        $log = ActivityLog::sole();
        $this->assertSame('rejected', $log->result);
        $this->assertEmpty($log->after_values);
        $this->assertStringNotContainsString('SECRET', $log->toJson());
    }

    public function test_system_and_external_origins_do_not_inherit_logged_in_actor(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        foreach (['system', 'external'] as $origin) {
            $log = DB::transaction(fn () => app(ActivityLogService::class)->record('products', 'updated', 1, ['stock' => '1.000'], ['stock' => '2.000'], null, $origin));
            $this->assertNull($log->actor_id);
            $this->assertSame($origin, $log->origin);
        }
    }

    public function test_model_prevents_normal_update_and_delete(): void
    {
        $log = ActivityLog::factory()->create();
        try {
            $log->update(['action' => 'tampered']);
            $this->fail();
        } catch (\LogicException) {
            $this->assertSame('test.created', $log->fresh()->action);
        }
        try {
            $log->delete();
            $this->fail();
        } catch (\LogicException) {
            $this->assertModelExists($log);
        }
    }

    public function test_date_filter_accepts_only_end_date_and_uses_argentina_day_boundaries(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->travelTo(CarbonImmutable::parse('2026-10-09 02:59:00', 'UTC'));
        DB::transaction(fn () => app(ActivityLogService::class)->record('products', 'updated', 1, ['name' => 'A'], ['name' => 'B']));
        $this->travelTo(CarbonImmutable::parse('2026-10-09 03:00:00', 'UTC'));
        DB::transaction(fn () => app(ActivityLogService::class)->record('products', 'updated', 2, ['name' => 'A'], ['name' => 'B']));
        $this->get('/admin/activity-logs?to=2026-10-08')->assertOk()->assertViewHas('logs', fn ($logs) => $logs->total() === 1 && $logs->first()->entity_id === '1');
        $this->get('/admin/activity-logs?from=2026-10-09&to=2026-10-08')->assertSessionHasErrors('to');
    }

    public function test_csrf_rejection_cannot_modify_product_or_create_success_log(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $product = Product::factory()->create(['name' => 'Original']);
        $this->app->instance('env', 'local');
        $this->put('/admin/products/'.$product->id, $this->productData())->assertStatus(419);
        $this->assertSame('Original', $product->fresh()->name);
        $this->assertDatabaseCount('activity_logs', 0);
    }
}
