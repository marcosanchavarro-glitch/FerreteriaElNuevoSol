<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Services\Catalog\ImageStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ImageManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('catalog');
    }

    private function base(Product $product): string
    {
        return '/admin/products/'.$product->id.'/images';
    }

    private function upload(Product $product, ?string $alt = null): ProductImage
    {
        $this->post($this->base($product), ['image' => UploadedFile::fake()->image('photo.png', 120, 80), 'alt_text' => $alt])->assertRedirect();

        return $product->images()->latest('id')->firstOrFail();
    }

    public static function formats(): array
    {
        return [['jpeg'], ['jpg'], ['png'], ['webp']];
    }

    #[DataProvider('formats')]
    public function test_valid_images_are_normalized_to_private_webp_with_generated_names(string $extension): void
    {
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->admin()->create());
        $file = UploadedFile::fake()->image('original name.'.$extension, 80, 60);
        $this->post($this->base($product), ['image' => $file, 'alt_text' => 'Taladro rojo'])->assertRedirect();
        $image = $product->images()->sole();
        $this->assertMatchesRegularExpression('#^products/'.$product->id.'/[a-f0-9-]{36}\.webp$#', $image->path);
        $this->assertTrue($image->is_primary);
        $this->assertSame('Taladro rojo', $image->alt_text);
        Storage::disk('catalog')->assertExists($image->path);
        $info = getimagesizefromstring(Storage::disk('catalog')->get($image->path));
        $this->assertSame('image/webp', $info['mime']);
        $this->assertSame(80, $info[0]);
        $this->assertSame(60, $info[1]);
        $this->get($this->base($product))->assertOk()->assertSee('Taladro rojo')->assertSee('Principal');
        $this->get($this->base($product).'/'.$image->id)->assertOk()->assertHeader('Content-Type', 'image/webp')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get('/storage/'.$image->path)->assertForbidden();
    }

    public function test_gallery_primary_order_alt_and_delete_promote_first_remaining(): void
    {
        $product = Product::factory()->create();
        $base = $this->base($product);
        $this->actingAs(User::factory()->seller()->create());
        $a = $this->upload($product);
        $b = $this->upload($product);
        $c = $this->upload($product);
        $this->assertSame(1, $product->images()->where('is_primary', true)->count());
        $this->assertFalse($b->is_primary);
        $this->assertNull($a->alt_text);
        $this->post($base.'/'.$c->id.'/primary')->assertRedirect();
        $this->assertFalse($a->fresh()->is_primary);
        $this->assertTrue($c->fresh()->is_primary);
        $this->assertSame(1, $product->images()->where('is_primary', true)->count());
        $this->post($base.'/'.$b->id.'/move', ['direction' => 'up'])->assertRedirect();
        $this->assertSame([$b->id, $a->id, $c->id], $product->images()->orderBy('sort_order')->pluck('id')->all());
        $this->put($base.'/'.$b->id, ['alt_text' => 'Vista lateral'])->assertRedirect();
        $this->assertSame('Vista lateral', $b->fresh()->alt_text);
        $this->delete($base.'/'.$c->id)->assertRedirect();
        $this->assertModelMissing($c);
        Storage::disk('catalog')->assertMissing($c->path);
        $this->assertTrue($b->fresh()->is_primary);
        $this->assertFalse($a->fresh()->is_primary);
        $this->delete($base.'/'.$a->id)->assertRedirect();
        Storage::disk('catalog')->assertMissing($a->path);
        $this->assertTrue($b->fresh()->is_primary);
        $this->delete($base.'/'.$b->id)->assertRedirect();
        $this->assertSame(0, $product->images()->count());
        $this->assertSame([], Storage::disk('catalog')->allFiles());
        $new = $this->upload($product);
        $this->assertTrue($new->is_primary);
    }

    public function test_move_edges_and_down_keep_a_stable_contiguous_order(): void
    {
        $product = Product::factory()->create();
        $base = $this->base($product);
        $this->actingAs(User::factory()->admin()->create());
        $a = $this->upload($product);
        $b = $this->upload($product);
        $this->post($base.'/'.$a->id.'/move', ['direction' => 'up'])->assertRedirect();
        $this->post($base.'/'.$b->id.'/move', ['direction' => 'down'])->assertRedirect();
        $this->assertSame([$a->id, $b->id], $product->images()->orderBy('sort_order')->pluck('id')->all());
        $this->post($base.'/'.$a->id.'/move', ['direction' => 'down'])->assertRedirect();
        $this->assertSame([$b->id, $a->id], $product->images()->orderBy('sort_order')->pluck('id')->all());
        $this->assertSame([0, 1], $product->images()->orderBy('sort_order')->pluck('sort_order')->all());
        $this->assertTrue($a->fresh()->is_primary);
        $this->postJson($base.'/'.$a->id.'/move', ['direction' => 'sideways'])->assertUnprocessable()->assertJsonValidationErrors('direction');
    }

    public static function badFiles(): array
    {
        return [['spoof'], ['svg'], ['gif'], ['extension'], ['size'], ['width'], ['height'], ['corrupt']];
    }

    #[DataProvider('badFiles')]
    public function test_invalid_mime_extension_size_dimensions_and_corrupt_content_are_rejected(string $kind): void
    {
        $file = match ($kind) {
            'spoof' => UploadedFile::fake()->createWithContent('malicious.jpg', '<?php echo "bad";'),
            'svg' => UploadedFile::fake()->createWithContent('image.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            'gif' => UploadedFile::fake()->image('animation.gif'),
            'extension' => UploadedFile::fake()->image('image.php'),
            'size' => UploadedFile::fake()->image('big.png')->size(5121),
            'width' => UploadedFile::fake()->image('wide.png', 3001, 1),
            'height' => UploadedFile::fake()->image('tall.png', 1, 3001),
            'corrupt' => UploadedFile::fake()->createWithContent('broken.png', "\x89PNG\r\n\x1a\nnot an image"),
        };
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->admin()->create())->postJson($this->base($product), ['image' => $file])->assertUnprocessable()->assertJsonValidationErrors('image');
        $this->assertDatabaseCount('product_images', 0);
        $this->assertSame([], Storage::disk('catalog')->allFiles());
    }

    public function test_original_filename_and_embedded_trailing_code_are_not_preserved(): void
    {
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->admin()->create());
        $source = UploadedFile::fake()->image('valid.png');
        $file = UploadedFile::fake()->createWithContent('../danger.php.png', file_get_contents($source->getRealPath()).'<?php echo "payload";');
        $this->post($this->base($product), ['image' => $file])->assertRedirect();
        $image = $product->images()->sole();
        $this->assertStringNotContainsString('danger', $image->path);
        $this->assertStringNotContainsString('payload', Storage::disk('catalog')->get($image->path));
    }

    public function test_wrong_parent_cannot_read_edit_promote_move_or_delete_image(): void
    {
        $owner = Product::factory()->create();
        $other = Product::factory()->create();
        $this->actingAs(User::factory()->admin()->create());
        $image = $this->upload($owner);
        $wrong = $this->base($other).'/'.$image->id;
        $this->get($wrong)->assertNotFound();
        $this->put($wrong, ['alt_text' => 'bad'])->assertNotFound();
        $this->post($wrong.'/primary')->assertNotFound();
        $this->post($wrong.'/move', ['direction' => 'up'])->assertNotFound();
        $this->delete($wrong)->assertNotFound();
        $this->assertModelExists($image);
        Storage::disk('catalog')->assertExists($image->path);
    }

    public function test_arbitrary_paths_and_primary_flags_cannot_be_posted_as_metadata(): void
    {
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->admin()->create());
        $image = $this->upload($product);
        $base = $this->base($product);
        foreach (['path' => '../../secret', 'product_id' => 999, 'is_primary' => '1', 'sort_order' => 100] as $field => $value) {
            $this->put($base.'/'.$image->id, ['alt_text' => 'safe', $field => $value])->assertForbidden();
            $this->post($base, ['image' => UploadedFile::fake()->image('a.jpg'), $field => $value])->assertForbidden();
        }
        $this->putJson($base.'/'.$image->id, ['alt_text' => str_repeat('a', 256)])->assertUnprocessable();
        $this->assertSame($image->path, $image->fresh()->path);
        $this->assertSame(1, $product->images()->count());
    }

    public static function staff(): array
    {
        return [['admin'], ['seller']];
    }

    #[DataProvider('staff')]
    public function test_category_upload_replace_preview_delete_reuses_safe_storage(string $role): void
    {
        $category = Category::factory()->create();
        $url = '/admin/categories/'.$category->id.'/image';
        $this->actingAs(User::factory()->create(['role' => $role]));
        $this->post($url, ['image' => UploadedFile::fake()->image('category.jpg')])->assertRedirect();
        $first = $category->fresh()->image_path;
        $this->assertStringStartsWith('categories/'.$category->id.'/', $first);
        Storage::disk('catalog')->assertExists($first);
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/webp');
        $this->get('/admin/categories/'.$category->id.'/edit')->assertOk();
        $this->post($url, ['image' => UploadedFile::fake()->image('replacement.webp')])->assertRedirect();
        $second = $category->fresh()->image_path;
        $this->assertNotSame($first, $second);
        Storage::disk('catalog')->assertMissing($first);
        Storage::disk('catalog')->assertExists($second);
        $this->postJson($url, ['image' => UploadedFile::fake()->createWithContent('bad.jpg', 'bad')])->assertUnprocessable();
        $this->assertSame($second, $category->fresh()->image_path);
        $this->delete($url)->assertRedirect();
        $this->assertNull($category->fresh()->image_path);
        Storage::disk('catalog')->assertMissing($second);
        $this->get($url)->assertNotFound();
    }

    public function test_customer_and_guest_cannot_access_any_image_operation(): void
    {
        $product = Product::factory()->create();
        $category = Category::factory()->create();
        $this->actingAs(User::factory()->admin()->create());
        $image = $this->upload($product);
        $base = $this->base($product);
        $item = $base.'/'.$image->id;
        $categoryUrl = '/admin/categories/'.$category->id.'/image';
        $this->flushSession();
        $this->actingAs(User::factory()->create());
        foreach ([$base, $item, $categoryUrl] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->post($base, ['image' => UploadedFile::fake()->image('a.jpg')])->assertForbidden();
        $this->put($item, ['alt_text' => 'bad'])->assertForbidden();
        $this->post($item.'/primary')->assertForbidden();
        $this->post($item.'/move', ['direction' => 'up'])->assertForbidden();
        $this->delete($item)->assertForbidden();
        $this->post($categoryUrl, ['image' => UploadedFile::fake()->image('a.jpg')])->assertForbidden();
        $this->delete($categoryUrl)->assertForbidden();
        $this->post('/logout');
        $this->get($item)->assertRedirect('/login');
        $this->get($categoryUrl)->assertRedirect('/login');
        $this->assertModelExists($image);
        Storage::disk('catalog')->assertExists($image->path);
    }

    public function test_seller_can_upload_editorial_images_to_external_products_without_commercial_changes(): void
    {
        $product = Product::factory()->create(['external_id' => 'EX', 'price' => 100051, 'stock' => '0.501']);
        $this->actingAs(User::factory()->seller()->create());
        $this->upload($product);
        $this->assertSame(100051, $product->fresh()->price);
        $this->assertSame('0.501', $product->fresh()->stock);
    }

    public function test_unsafe_stored_path_cannot_delete_another_file(): void
    {
        $product = Product::factory()->create();
        $image = $product->images()->create(['path' => '../private/secret.txt', 'is_primary' => true, 'sort_order' => 0]);
        Storage::disk('catalog')->put('secret.txt', 'keep');
        $this->actingAs(User::factory()->admin()->create())->deleteJson($this->base($product).'/'.$image->id)->assertUnprocessable();
        $this->assertModelExists($image);
        $this->assertSame('keep', Storage::disk('catalog')->get('secret.txt'));
    }

    public function test_storage_delete_failure_rolls_back_gallery_and_preserves_primary(): void
    {
        $product = Product::factory()->create();
        $this->actingAs(User::factory()->admin()->create());
        $image = $this->upload($product);
        $second = $this->upload($product);
        $this->partialMock(ImageStorage::class, function ($mock): void {
            $mock->shouldReceive('delete')->once()->andThrow(new \RuntimeException('disk failure'));
        });
        $this->delete($this->base($product).'/'.$image->id)->assertStatus(500);
        $this->assertModelExists($image);
        $this->assertTrue($image->fresh()->is_primary);
        $this->assertFalse($second->fresh()->is_primary);
        Storage::disk('catalog')->assertExists($image->path);
    }

    public function test_category_replace_failure_restores_reference_and_removes_new_file(): void
    {
        $category = Category::factory()->create();
        $url = '/admin/categories/'.$category->id.'/image';
        $this->actingAs(User::factory()->admin()->create());
        $this->post($url, ['image' => UploadedFile::fake()->image('first.jpg')])->assertRedirect();
        $old = $category->fresh()->image_path;
        $storage = app(ImageStorage::class);
        $this->partialMock(ImageStorage::class, function ($mock) use ($old, $storage): void {
            $mock->shouldReceive('delete')->with($old, 'categories/'.Category::sole()->id)->once()->andThrow(new \RuntimeException('disk failure'));
            $mock->shouldReceive('delete')->withArgs(fn (string $path, string $folder): bool => $path !== $old)->once()->andReturnUsing(fn (string $path, string $folder) => $storage->delete($path, $folder));
        });
        $this->post($url, ['image' => UploadedFile::fake()->image('second.jpg')])->assertStatus(500);
        $this->assertSame($old, $category->fresh()->image_path);
        $this->assertSame([$old], Storage::disk('catalog')->allFiles());
    }
}
