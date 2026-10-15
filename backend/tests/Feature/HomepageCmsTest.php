<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\HomepageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HomepageCmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('catalog');
    }

    private function data(array $changes = []): array
    {
        return array_merge(['type' => 'hero', 'title' => 'Mi portada', 'subtitle' => 'Subtítulo', 'content' => 'Contenido de prueba', 'active' => 1, 'sort_order' => 0, 'button_text' => 'Ver productos', 'button_url' => '/productos', 'max_items' => 6], $changes);
    }

    public static function roles(): array
    {
        return [['admin'], ['seller']];
    }

    #[DataProvider('roles')]
    public function test_staff_can_create_edit_hide_and_reactivate_sections(string $role): void
    {
        $this->actingAs(User::factory()->create(['role' => $role]));
        $this->get('/admin/content/sections/create')->assertOk();
        $this->post('/admin/content/sections', $this->data())->assertRedirect()->assertSessionHasNoErrors();
        $record = HomepageSection::firstOrFail();
        $this->get('/admin/content/sections/'.$record->id.'/edit')->assertOk();
        $this->put('/admin/content/sections/'.$record->id, $this->data(['title' => 'Portada editada']))->assertSessionHasNoErrors();
        $this->get('/')->assertSee('Portada editada');
        $this->patch('/admin/content/sections/'.$record->id.'/visibility', ['active' => 0])->assertSessionHasErrors('confirm');
        $this->patch('/admin/content/sections/'.$record->id.'/visibility', ['active' => 0, 'confirm' => 1])->assertSessionHasNoErrors();
        $this->get('/')->assertDontSee('Portada editada')->assertSee('Ver catálogo');
        $this->patch('/admin/content/sections/'.$record->id.'/visibility', ['active' => 1, 'confirm' => 1])->assertSessionHasNoErrors();
        $this->get('/')->assertSee('Portada editada');
    }

    public static function types(): array
    {
        return array_map(fn ($type) => [$type], ['hero', 'banner', 'featured_products', 'featured_categories', 'text_image', 'promotion', 'text', 'image']);
    }

    #[DataProvider('types')]
    public function test_controlled_types_are_editable_and_rendered(string $type): void
    {
        Banner::factory()->create(['title' => 'Banner visible', 'active' => true]);
        Product::factory()->create(['status' => 'published', 'featured' => true, 'name' => 'Producto destacado real']);
        Category::factory()->create(['featured' => true, 'name' => 'Categoría destacada real']);
        $this->actingAs(User::factory()->seller()->create())->post('/admin/content/sections', $this->data(['type' => $type, 'title' => 'Sección '.$type]))->assertSessionHasNoErrors();
        $response = $this->get('/')->assertOk()->assertSee('Sección '.$type);
        if ($type === 'featured_products') {
            $response->assertSee('Producto destacado real');
        }
        if ($type === 'featured_categories') {
            $response->assertSee('Categoría destacada real');
        }
        if ($type === 'banner') {
            $response->assertSee('Banner visible');
        }
    }

    public function test_guests_and_customers_cannot_read_or_mutate_cms(): void
    {
        $section = HomepageSection::factory()->create();
        $banner = Banner::factory()->create();
        foreach ([null, User::factory()->create()] as $user) {
            if ($user) {
                $this->actingAs($user);
            }
            foreach (['sections' => $section, 'banners' => $banner] as $kind => $record) {
                foreach (['get' => ['', '/create', '/'.$record->id.'/edit', '/'.$record->id.'/image'], 'post' => ['', '/'.$record->id.'/move'], 'put' => ['/'.$record->id], 'patch' => ['/'.$record->id.'/visibility']] as $method => $paths) {
                    foreach ($paths as $path) {
                        $response = $this->$method('/admin/content/'.$kind.$path);
                        if ($user) {
                            $response->assertForbidden();
                        } else {
                            $response->assertRedirect('/login');
                        }
                    }
                }
            }
        }
    }

    public function test_ordering_is_stable_even_with_equal_positions_and_hidden_rows(): void
    {
        $a = HomepageSection::factory()->create(['title' => 'Sección primera', 'type' => 'text_image', 'active' => true, 'sort_order' => 0]);
        $b = HomepageSection::factory()->create(['title' => 'Sección segunda', 'type' => 'promotion', 'active' => true, 'sort_order' => 0]);
        HomepageSection::factory()->create(['title' => 'Secreto inactivo', 'active' => false]);
        $this->get('/')->assertSeeInOrder(['Sección primera', 'Sección segunda'])->assertDontSee('Secreto inactivo');
        $this->actingAs(User::factory()->admin()->create())->post('/admin/content/sections/'.$b->id.'/move', ['direction' => 'up'])->assertSessionHasNoErrors();
        $this->get('/')->assertSeeInOrder(['Sección segunda', 'Sección primera']);
        $this->post('/admin/content/sections/'.$b->id.'/move', ['direction' => 'up'])->assertSessionHasNoErrors();
        $this->assertSame(0, $b->fresh()->sort_order);
    }

    public static function badLinks(): array
    {
        return [['javascript:alert(1)'], ['https://example.com'], ['//evil.test'], ['/\\evil.test'], ['/%2f/evil.test'], ['/%0aevil'], ['/%252f/evil']];
    }

    #[DataProvider('badLinks')]
    public function test_unsafe_links_rejected(string $link): void
    {
        $this->actingAs(User::factory()->admin()->create())->post('/admin/content/sections', $this->data(['button_url' => $link]))->assertSessionHasErrors('button_url');
        $this->assertDatabaseCount('homepage_sections', 0);
    }

    public function test_types_paths_and_settings_cannot_be_injected_and_text_is_escaped(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        foreach (['type' => 'arbitrary', 'image_path' => '../../.env', 'settings' => ['template' => 'evil']] as $field => $value) {
            $this->post('/admin/content/sections', $this->data([$field => $value]))->assertSessionHasErrors($field);
        }
        $this->post('/admin/content/sections', $this->data(['title' => '<script>alert(1)</script>', 'button_url' => '/productos?q=taladro']))->assertSessionHasNoErrors();
        $this->get('/')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        HomepageSection::first()->update(['button_url' => 'javascript:alert(1)']);
        $this->get('/')->assertDontSee('href="javascript:', false);
    }

    public function test_featured_limits_publication_and_category_visibility(): void
    {
        HomepageSection::factory()->create(['type' => 'featured_products', 'active' => true, 'settings' => ['max_items' => 2]]);
        HomepageSection::factory()->create(['type' => 'featured_categories', 'active' => true, 'settings' => ['max_items' => 1]]);
        Product::factory()->count(3)->create(['featured' => true, 'status' => 'published']);
        Product::factory()->create(['featured' => true, 'name' => 'Privado', 'status' => 'private']);
        Category::factory()->create(['featured' => true, 'active' => false, 'name' => 'Categoría oculta']);
        $parent = Category::factory()->create(['active' => false]);
        Category::factory()->create(['featured' => true, 'parent_id' => $parent->id, 'name' => 'Hija oculta']);
        Category::factory()->count(2)->create(['featured' => true]);
        $this->get('/')->assertOk()->assertViewHas('products', fn ($p) => $p->count() === 2)->assertDontSee('Privado')->assertDontSee('Categoría oculta')->assertDontSee('Hija oculta');
    }

    public static function validImages(): array
    {
        return [['png'], ['jpg'], ['webp']];
    }

    #[DataProvider('validImages')]
    public function test_images_normalized_replaced_removed_and_private_when_hidden(string $extension): void
    {
        $this->actingAs(User::factory()->admin()->create())->post('/admin/content/sections', $this->data(['image' => UploadedFile::fake()->image('hero.'.$extension, 120, 80)]))->assertSessionHasNoErrors();
        $record = HomepageSection::firstOrFail();
        $old = $record->image_path;
        $this->assertMatchesRegularExpression('#^sections/'.$record->id.'/[a-f0-9-]+\.webp$#', $old);
        Storage::disk('catalog')->assertExists($old);
        $this->get('/imagenes/contenido/sections/'.$record->id)->assertOk()->assertHeader('Content-Type', 'image/webp')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->put('/admin/content/sections/'.$record->id, $this->data(['image' => UploadedFile::fake()->image('replacement.png')]))->assertSessionHasNoErrors();
        Storage::disk('catalog')->assertMissing($old);
        $record->refresh();
        $new = $record->image_path;
        $record->update(['active' => false]);
        $this->get('/imagenes/contenido/sections/'.$record->id)->assertNotFound();
        $this->get('/admin/content/sections/'.$record->id.'/image')->assertOk();
        $this->delete('/admin/content/sections/'.$record->id.'/image')->assertSessionHasErrors('confirm');
        $this->delete('/admin/content/sections/'.$record->id.'/image', ['confirm' => 1])->assertSessionHasNoErrors();
        Storage::disk('catalog')->assertMissing($new);
        $this->assertNull($record->fresh()->image_path);
    }

    public function test_invalid_uploads_rejected_without_records_or_files(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        foreach ([UploadedFile::fake()->createWithContent('script.php', '<?php echo 1;'), UploadedFile::fake()->createWithContent('fake.jpg', '<?php echo 1;'), UploadedFile::fake()->createWithContent('bad.svg', '<svg onload="alert(1)"></svg>'), UploadedFile::fake()->image('huge.png', 3001, 1), UploadedFile::fake()->image('large.png')->size(5121)] as $file) {
            $this->post('/admin/content/sections', $this->data(['image' => $file]))->assertSessionHasErrors('image');
        }
        $this->assertDatabaseCount('homepage_sections', 0);
        $this->assertSame([], Storage::disk('catalog')->allFiles());
    }

    public function test_banner_required_image_order_visibility_and_dates(): void
    {
        HomepageSection::factory()->create(['type' => 'banner', 'active' => true]);
        $this->actingAs(User::factory()->seller()->create());
        $data = ['title' => 'Banner nuevo', 'subtitle' => 'Promo', 'active' => 1, 'sort_order' => 2, 'button_text' => 'Ver', 'button_url' => '/productos'];
        $this->post('/admin/content/banners', $data)->assertSessionHasErrors('image');
        $this->post('/admin/content/banners', $data + ['image' => UploadedFile::fake()->image('banner.png')])->assertSessionHasNoErrors();
        $banner = Banner::firstOrFail();
        $other = Banner::factory()->create(['title' => 'Otro banner', 'active' => true, 'sort_order' => 0]);
        $this->get('/')->assertSeeInOrder(['Otro banner', 'Banner nuevo']);
        $this->post('/admin/content/banners/'.$banner->id.'/move', ['direction' => 'up'])->assertSessionHasNoErrors();
        $this->get('/')->assertSeeInOrder(['Banner nuevo', 'Otro banner']);
        $this->put('/admin/content/banners/'.$banner->id, array_merge($data, ['title' => 'Banner editado']))->assertSessionHasNoErrors();
        $this->get('/')->assertSee('Banner editado');
        $this->patch('/admin/content/banners/'.$banner->id.'/visibility', ['active' => 0, 'confirm' => 1])->assertSessionHasNoErrors();
        $this->get('/')->assertDontSee('Banner editado');
        $this->get('/imagenes/contenido/banners/'.$banner->id)->assertNotFound();
        $other->update(['starts_at' => now()->addDay()]);
        $this->get('/')->assertDontSee('Otro banner');
        $other->update(['starts_at' => null, 'ends_at' => now()->subMinute()]);
        $this->get('/')->assertDontSee('Otro banner');
    }

    public function test_image_storage_failure_rolls_back_content_and_removes_new_upload(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        HomepageSection::updated(function (HomepageSection $section): void {
            if ($section->wasChanged('image_path')) {
                throw new \RuntimeException('Test rollback');
            }
        });
        try {
            $this->post('/admin/content/sections', $this->data(['image' => UploadedFile::fake()->image('hero.png')]))->assertSessionHasErrors('image');
            $this->assertDatabaseCount('homepage_sections', 0);
            $this->assertSame([], Storage::disk('catalog')->allFiles());
        } finally {
            HomepageSection::flushEventListeners();
        }
    }

    public function test_form_deactivation_requires_confirmation_and_seed_does_not_overwrite(): void
    {
        $this->seed(HomepageSeeder::class);
        $record = HomepageSection::first();
        $record->update(['title' => 'Título propio']);
        $this->seed(HomepageSeeder::class);
        $this->assertSame('Título propio', $record->fresh()->title);
        $this->assertDatabaseCount('homepage_sections', 6);
        $this->actingAs(User::factory()->admin()->create());
        $this->put('/admin/content/sections/'.$record->id, $this->data(['active' => 0]))->assertSessionHasErrors('confirm_deactivate');
        $this->put('/admin/content/sections/'.$record->id, $this->data(['active' => 0, 'confirm_deactivate' => 1]))->assertSessionHasNoErrors();
        $this->assertFalse($record->fresh()->active);
    }
}
