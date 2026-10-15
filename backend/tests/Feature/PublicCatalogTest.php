<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Catalog\CategoryImages;
use App\Services\Catalog\ProductGallery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('catalog');
    }

    private function published(array $data = []): Product
    {
        return Product::factory()->create(array_merge(['status' => 'published'], $data));
    }

    public function test_catalog_only_lists_published_and_home_only_public_featured(): void
    {
        $visible = $this->published(['name' => 'Visible destacado', 'featured' => true]);
        $normal = $this->published(['name' => 'Visible normal']);
        Product::factory()->create(['name' => 'Borrador secreto', 'featured' => true]);
        Product::factory()->create(['name' => 'Privado secreto', 'featured' => true, 'status' => 'private']);
        $this->get('/productos')->assertOk()->assertSee($visible->name)->assertSee($normal->name)->assertDontSee('Borrador secreto')->assertDontSee('Privado secreto');
        $this->get('/')->assertOk()->assertSee($visible->name)->assertDontSee($normal->name)->assertDontSee('Borrador secreto')->assertDontSee('Privado secreto');
    }

    public static function hiddenStatuses(): array
    {
        return [['draft'], ['private']];
    }

    #[DataProvider('hiddenStatuses')]
    public function test_hidden_products_are_404_even_for_admin(string $status): void
    {
        $product = Product::factory()->create(['status' => $status]);
        $this->get('/productos/'.$product->slug)->assertNotFound();
        $this->actingAs(User::factory()->admin()->create())->get('/productos/'.$product->slug)->assertNotFound();
    }

    public function test_simple_product_displays_exact_price_unit_categories_description_without_internal_data(): void
    {
        $product = $this->published(['name' => 'Cable medido', 'price' => 250051, 'stock' => '123.456', 'sale_unit' => 'meter', 'sku' => 'CAB-01', 'external_id' => 'SECRET-EXTERNAL', 'description' => 'Descripción útil']);
        $category = Category::factory()->create(['name' => 'Electricidad']);
        $product->categories()->attach($category);
        $this->get('/productos/'.$product->slug)->assertOk()->assertSee('$ 2.500,51')->assertSee('Metro')->assertSee('Disponible')->assertSee('CAB-01')->assertSee('Descripción útil')->assertSee('Electricidad')->assertDontSee('123.456')->assertDontSee('123456')->assertDontSee('SECRET-EXTERNAL')->assertSee('<meta name="description"', false);
    }

    public function test_inquiry_never_displays_internal_price(): void
    {
        $product = $this->published(['purchase_mode' => 'inquiry', 'price' => 987654321, 'stock' => null]);
        foreach (['/productos', '/productos/'.$product->slug] as $url) {
            $this->get($url)->assertOk()->assertSee('Precio a confirmar')->assertDontSee('9.876.543,21')->assertSee('Consultar disponibilidad');
        }
    }

    public function test_zero_and_negative_stock_remain_visible_as_out_of_stock(): void
    {
        foreach (['0.000', '-0.001'] as $stock) {
            $product = $this->published(['stock' => $stock]);
            $this->get('/productos/'.$product->slug)->assertOk()->assertSee('Sin stock');
        }
    }

    public function test_active_variants_can_be_selected_without_javascript_and_inactive_are_not_exposed(): void
    {
        $product = Product::factory()->variable()->create(['status' => 'published', 'sale_unit' => 'liter']);
        $first = ProductVariant::factory()->create(['product_id' => $product->id, 'name' => '1 L', 'price' => 850051, 'stock' => '1.001', 'sku' => 'L1']);
        $second = ProductVariant::factory()->create(['product_id' => $product->id, 'name' => '4 L', 'price' => 2700000, 'stock' => '0.000', 'sku' => 'L4']);
        $hidden = ProductVariant::factory()->create(['product_id' => $product->id, 'name' => 'OCULTA', 'sku' => 'HIDDEN', 'active' => false]);
        $this->get('/productos/'.$product->slug)->assertOk()->assertSee('$ 8.500,51')->assertSee('data-variant-selector', false)->assertDontSee('OCULTA')->assertDontSee('HIDDEN')->assertDontSee('1.001');
        $this->get('/productos/'.$product->slug.'?variante='.$second->id)->assertOk()->assertViewHas('selected', fn ($v) => $v->id === $second->id)->assertViewHas('offer', fn ($o) => $o['price'] === '$ 27.000,00' && $o['availability'] === 'Sin stock' && $o['sku'] === 'L4');
        $this->get('/productos/'.$product->slug.'?variante='.$hidden->id)->assertNotFound();
        $other = ProductVariant::factory()->create();
        $this->get('/productos/'.$product->slug.'?variante='.$other->id)->assertNotFound();
    }

    public function test_variable_price_uses_only_active_calculated_variants_and_handles_missing_offers(): void
    {
        $product = Product::factory()->variable()->create(['status' => 'published']);
        ProductVariant::factory()->create(['product_id' => $product->id, 'price' => 850000]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'price' => 1, 'purchase_mode' => 'inquiry']);
        ProductVariant::factory()->create(['product_id' => $product->id, 'price' => 2, 'active' => false]);
        $this->get('/productos')->assertOk()->assertSee('Desde $ 8.500,00')->assertSee('Otras presentaciones: precio a confirmar.')->assertDontSee('$ 0,01');
        $empty = Product::factory()->variable()->create(['status' => 'published']);
        $this->get('/productos/'.$empty->slug)->assertOk()->assertSee('Sin presentaciones disponibles');
    }

    public function test_category_hierarchy_includes_visible_descendants_without_duplicate_products(): void
    {
        $root = Category::factory()->create(['name' => 'Herramientas']);
        $child = Category::factory()->create(['parent_id' => $root->id, 'name' => 'Eléctricas']);
        $leaf = Category::factory()->create(['parent_id' => $child->id, 'name' => 'Taladros']);
        $product = $this->published();
        $product->categories()->attach([$root->id, $leaf->id]);
        $this->get('/categorias/'.$root->slug)->assertOk()->assertSee('Eléctricas')->assertViewHas('products', fn ($p) => $p->total() === 1);
        $this->get('/categorias/'.$leaf->slug)->assertOk()->assertSeeInOrder(['Herramientas', 'Eléctricas', 'Taladros'])->assertSee($product->name);
        $this->get('/categorias/'.$child->slug)->assertOk()->assertSee('Taladros')->assertSee($product->name);
    }

    public function test_inactive_ancestors_hide_categories_images_navigation_and_search_associations(): void
    {
        $root = Category::factory()->create(['active' => false, 'name' => 'SecretRoot']);
        $child = Category::factory()->create(['parent_id' => $root->id, 'active' => true, 'name' => 'SecretChild']);
        $product = $this->published();
        $product->categories()->attach($child);
        $this->get('/categorias/'.$root->slug)->assertNotFound();
        $this->get('/categorias/'.$child->slug)->assertNotFound();
        $this->get('/imagenes/categorias/'.$child->slug)->assertNotFound();
        $this->get('/productos/'.$product->slug)->assertOk()->assertDontSee('SecretChild')->assertDontSee('SecretRoot');
        $this->get('/productos?q=SecretChild')->assertViewHas('products', fn ($p) => $p->total() === 0);
    }

    public function test_gallery_primary_order_alt_missing_files_and_public_media_recheck_publication(): void
    {
        $product = $this->published();
        $gallery = app(ProductGallery::class);
        $first = $gallery->upload($product, UploadedFile::fake()->image('one.png'), 'Vista uno');
        $second = $gallery->upload($product, UploadedFile::fake()->image('two.png'), 'Vista dos');
        $gallery->primary($product, $second);
        $this->get('/productos/'.$product->slug)->assertOk()->assertSee('alt="Vista dos"', false)->assertSee('data-gallery-src', false)->assertViewHas('product', fn ($p) => $p->images->first()->id === $second->id);
        $this->get('/imagenes/productos/'.$second->id)->assertOk()->assertHeader('Content-Type', 'image/webp')->assertHeader('X-Content-Type-Options', 'nosniff');
        Storage::disk('catalog')->delete($second->path);
        $this->get('/productos')->assertOk()->assertSee('images/product-placeholder.svg');
        $this->get('/imagenes/productos/'.$second->id)->assertOk()->assertHeader('Content-Type', 'image/svg+xml');
        $product->update(['status' => 'private']);
        $this->get('/imagenes/productos/'.$first->id)->assertNotFound();
    }

    public function test_category_image_is_public_only_when_entire_branch_is_active(): void
    {
        $category = Category::factory()->create();
        app(CategoryImages::class)->replace($category, UploadedFile::fake()->image('category.png'));
        $this->get('/categorias/'.$category->slug)->assertOk()->assertSee('/imagenes/categorias/'.$category->slug);
        $this->get('/imagenes/categorias/'.$category->slug)->assertOk()->assertHeader('Content-Type', 'image/webp');
        $category->update(['active' => false]);
        $this->get('/imagenes/categorias/'.$category->slug)->assertNotFound();
    }

    public function test_related_products_share_visible_categories_exclude_self_and_hidden_and_are_limited(): void
    {
        $category = Category::factory()->create();
        $main = $this->published();
        $main->categories()->attach($category);
        Product::factory()->count(5)->create(['status' => 'published'])->each(fn ($p) => $p->categories()->attach($category));
        $secret = Product::factory()->create();
        $secret->categories()->attach($category);
        $this->get('/productos/'.$main->slug)->assertOk()->assertViewHas('related', fn ($related) => $related->count() === 3 && ! $related->contains('id', $main->id) && ! $related->contains('id', $secret->id));
    }

    public function test_empty_states_and_missing_urls_are_useful(): void
    {
        $category = Category::factory()->create();
        $this->get('/')->assertOk()->assertSee('Pronto vas a encontrar novedades.');
        $this->get('/productos')->assertOk()->assertSee('No hay productos');
        $this->get('/categorias/'.$category->slug)->assertOk()->assertSee('No hay productos');
        $this->get('/productos/no-existe')->assertNotFound();
        $this->get('/categorias/no-existe')->assertNotFound();
    }

    public function test_html_is_escaped_in_titles_attributes_description_and_variants(): void
    {
        $product = $this->published(['name' => '<script>alert(1)</script>', 'description' => '<img src=x onerror=alert(2)>']);
        $this->get('/productos/'.$product->slug)->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('<img src=x onerror=alert(2)>', false)->assertSee('&lt;script&gt;', false);
    }

    public function test_listing_query_count_does_not_grow_per_card(): void
    {
        $category = Category::factory()->create();
        $first = $this->published();
        $first->categories()->attach($category);
        DB::enableQueryLog();
        $this->get('/productos')->assertOk();
        $one = count(DB::getQueryLog());
        DB::disableQueryLog();
        Product::factory()->count(10)->create(['status' => 'published'])->each(fn ($p) => $p->categories()->attach($category));
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get('/productos')->assertOk();
        $many = count(DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertLessThanOrEqual($one + 1, $many);
        $this->assertLessThanOrEqual(10, $many);
    }

    public function test_public_media_never_reads_an_arbitrary_stored_path(): void
    {
        $product = $this->published();
        $image = $product->images()->create(['path' => '../secret.txt', 'is_primary' => true, 'sort_order' => 0]);
        Storage::disk('catalog')->put('secret.txt', 'PRIVATE DATA');
        $this->get('/imagenes/productos/'.$image->id)->assertOk()->assertHeader('Content-Type', 'image/svg+xml')->assertDontSee('PRIVATE DATA');
        $this->get('/productos/'.$product->slug)->assertOk()->assertSee('product-placeholder.svg')->assertDontSee('../secret.txt');
    }

    public function test_category_filters_stay_in_the_current_visible_branch(): void
    {
        $root = Category::factory()->create();
        Category::factory()->create(['parent_id' => $root->id]);
        $outside = Category::factory()->create();
        $product = $this->published();
        $product->categories()->attach($outside);
        $this->get('/categorias/'.$root->slug.'?categoria='.$outside->slug)->assertOk()->assertViewHas('products', fn ($p) => $p->total() === 0)->assertViewHas('filterCategoryRows', fn ($rows) => count($rows) === 2);
    }

    public function test_public_header_has_guest_and_authenticated_account_links(): void
    {
        $this->get('/')->assertOk()->assertSee('Ingresar');
        $this->actingAs(User::factory()->create())->get('/')->assertOk()->assertDontSee('Ingresar')->assertSee('Mi cuenta')->assertDontSee('href="'.route('admin.home').'"', false);
    }
}
