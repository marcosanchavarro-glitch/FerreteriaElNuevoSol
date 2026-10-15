<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\Catalog\CloudinaryImages;
use App\Services\Catalog\ImageStorage;
use App\Services\Catalog\ProductGallery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CloudinaryImagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['catalog.storage' => 'cloudinary', 'catalog.cloudinary' => ['cloud' => 'test-cloud', 'key' => 'test-key', 'secret' => 'test-secret', 'prefix' => 'test-demo']]);
        Http::preventStrayRequests();
    }

    private function parameters(Request $request): array
    {
        return $request->isMultipart()
            ? collect($request->data())->pluck('contents', 'name')->all()
            : $request->data();
    }

    public function test_upload_is_signed_authenticated_and_keeps_only_relative_path(): void
    {
        Http::fake(['*/image/upload' => fn ($request) => Http::response(['public_id' => $this->parameters($request)['public_id'], 'type' => 'authenticated', 'format' => 'webp'])]);
        $path = app(ImageStorage::class)->store(UploadedFile::fake()->image('photo.png'), 'products/1');
        $this->assertStringStartsWith('products/1/', $path);
        Http::assertSent(function ($request) use ($path): bool {
            $parameters = ['overwrite' => 'true', 'public_id' => 'test-demo/'.substr($path, 0, -5), 'timestamp' => $this->parameters($request)['timestamp'], 'type' => 'authenticated'];
            $signature = hash('sha256', implode('&', array_map(fn ($key, $value) => $key.'='.$value, array_keys($parameters), $parameters)).'test-secret');

            return $this->parameters($request)['type'] === 'authenticated' && $this->parameters($request)['signature'] === $signature && ! isset($this->parameters($request)['api_secret']);
        });
    }

    public function test_remote_backup_restore_and_deletion_use_same_identity(): void
    {
        $path = 'banners/1/aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa.webp';
        $bytes = 'RIFF0000WEBPtest';
        Http::fake([
            '*/image/download*' => Http::response($bytes),
            '*/image/upload' => fn ($request) => Http::response(['public_id' => $this->parameters($request)['public_id'], 'type' => 'authenticated', 'format' => 'webp']),
            '*/image/destroy' => Http::response(['result' => 'ok']),
        ]);
        $storage = app(ImageStorage::class);
        $this->assertSame($bytes, $storage->backup($path, 'banners/1'));
        $storage->delete($path, 'banners/1');
        $storage->restore($path, 'banners/1', $bytes);
        $response = $storage->response($path, 'banners/1');
        $this->assertSame('image/webp', $response->headers->get('Content-Type'));
        ob_start();
        $response->sendContent();
        $this->assertSame($bytes, ob_get_clean());
        Http::assertSent(fn ($request) => $this->parameters($request)['public_id'] === 'test-demo/banners/1/aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa' && $this->parameters($request)['type'] === 'authenticated');
    }

    public function test_failed_upload_does_not_create_a_database_reference(): void
    {
        Http::fake(['*' => Http::response(['error' => 'provider-details-must-not-leak'], 500)]);
        $product = Product::factory()->create();
        try {
            app(ProductGallery::class)->upload($product, UploadedFile::fake()->image('image.png'), null);
            $this->fail('Expected storage failure.');
        } catch (\RuntimeException $exception) {
            $this->assertStringNotContainsString('provider-details', $exception->getMessage());
            $this->assertDatabaseCount('product_images', 0);
        }
    }

    public function test_remote_paths_are_validated_before_network_access(): void
    {
        try {
            app(CloudinaryImages::class)->delete('../outside.webp');
            $this->fail('Expected validation failure.');
        } catch (ValidationException) {
            Http::assertNothingSent();
        }
    }
}
