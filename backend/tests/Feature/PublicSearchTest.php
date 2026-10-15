<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicSearchTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $data = []): Product
    {
        return Product::factory()->create(array_merge(['status' => 'published'], $data));
    }

    public static function searchFields(): array
    {
        return [['name', 'Taladro percutor', 'TALADRO'], ['sku', 'ABC-123', 'abc-123'], ['description', 'Uso profesional intenso', 'profesional'], ['short_description', 'Accesorio reforzado', 'REFORZADO'], ['name', 'MÁQUINA ELÉCTRICA', 'máquina eléctrica']];
    }

    #[DataProvider('searchFields')]
    public function test_search_matches_fields_case_insensitively(string $field, string $value, string $term): void
    {
        $match = $this->product([$field => $value]);
        $other = $this->product(['name' => 'No coincide']);
        $this->get('/productos?'.http_build_query(['q' => $term]))->assertOk()->assertViewHas('products', fn ($p) => $p->total() === 1 && $p->first()->id === $match->id);
    }

    public function test_spaces_tokens_categories_and_active_variant_codes_are_searchable(): void
    {
        $category = Category::factory()->create(['name' => 'Herramientas eléctricas']);
        $match = $this->product(['name' => 'Taladro   potente']);
        $match->categories()->attach($category);
        $this->get('/productos?'.http_build_query(['q' => '  TALADRO   potente  ']))->assertOk()->assertViewHas('filters', fn ($f) => $f['q'] === 'TALADRO potente')->assertViewHas('products', fn ($p) => $p->total() === 1);
        $this->get('/productos?q=el%C3%A9ctricas')->assertViewHas('products', fn ($p) => $p->first()->id === $match->id);
        $parent = Product::factory()->variable()->create(['status' => 'published']);
        ProductVariant::factory()->create(['product_id' => $parent->id, 'sku' => 'PINT-4L']);
        ProductVariant::factory()->create(['product_id' => $parent->id, 'sku' => 'PRIVATE-CODE', 'active' => false]);
        $this->get('/productos?q=pint-4l')->assertViewHas('products', fn ($p) => $p->total() === 1 && $p->first()->id === $parent->id);
        $this->get('/productos?q=private-code')->assertViewHas('products', fn ($p) => $p->total() === 0);
    }

    public function test_search_empty_results_and_literal_sql_wildcards(): void
    {
        $this->product(['name' => 'Taladro']);
        $literal = $this->product(['name' => 'Producto 100%_real']);
        $this->get('/productos?q=inexistente')->assertOk()->assertSee('No encontramos productos para tu búsqueda.');
        $this->get('/productos?q=%25_')->assertViewHas('products', fn ($p) => $p->total() === 1 && $p->first()->id === $literal->id);
        $this->get('/productos?'.http_build_query(['q' => "' OR 1=1 --"]))->assertOk()->assertViewHas('products', fn ($p) => $p->total() === 0);
    }

    public function test_combined_filters_require_one_matching_variant_and_support_inherited_values(): void
    {
        $category = Category::factory()->create(['slug' => 'cables']);
        $simple = $this->product(['sale_unit' => 'meter', 'stock' => '0.750']);
        $simple->categories()->attach($category);
        $parent = Product::factory()->variable()->create(['status' => 'published', 'sale_unit' => 'meter', 'purchase_mode' => 'calculated']);
        $parent->categories()->attach($category);
        ProductVariant::factory()->create(['product_id' => $parent->id, 'sale_unit' => 'liter', 'stock' => '10.000']);
        ProductVariant::factory()->create(['product_id' => $parent->id, 'stock' => '0.000']);
        $url = '/productos?categoria=cables&unidad=meter&disponibilidad=disponible&modalidad=calculated';
        $this->get($url)->assertOk()->assertViewHas('products', fn ($p) => $p->total() === 1 && $p->first()->id === $simple->id);
        ProductVariant::factory()->create(['product_id' => $parent->id, 'sale_unit' => null, 'purchase_mode' => null, 'stock' => '0.001']);
        $this->get($url)->assertViewHas('products', fn ($p) => $p->total() === 2);
        $this->get($url.'&tipo=variable')->assertViewHas('products', fn ($p) => $p->total() === 1 && $p->first()->id === $parent->id);
    }

    public static function availabilityFilters(): array
    {
        return [['disponible', '0.001'], ['sin-stock', '0.000'], ['sin-stock', '-1.000'], ['consultar', null]];
    }

    #[DataProvider('availabilityFilters')]
    public function test_availability_filters_use_exact_stock(string $filter, ?string $stock): void
    {
        $match = $this->product(['stock' => $stock]);
        $this->get('/productos?disponibilidad='.$filter)->assertOk()->assertViewHas('products', fn ($p) => $p->total() === 1 && $p->first()->id === $match->id);
    }

    public function test_mode_and_category_filters_exclude_hidden_products_and_inactive_variants(): void
    {
        $category = Category::factory()->create(['slug' => 'pinturas']);
        $match = $this->product(['purchase_mode' => 'inquiry']);
        $match->categories()->attach($category);
        $private = Product::factory()->create(['purchase_mode' => 'inquiry']);
        $private->categories()->attach($category);
        $parent = Product::factory()->variable()->create(['status' => 'published']);
        $parent->categories()->attach($category);
        ProductVariant::factory()->create(['product_id' => $parent->id, 'purchase_mode' => 'inquiry', 'active' => false]);
        $this->get('/productos?categoria=pinturas&modalidad=inquiry')->assertViewHas('products', fn ($p) => $p->total() === 1 && $p->first()->id === $match->id);
    }

    public function test_price_sort_uses_minimum_active_calculated_variant_and_unknowns_last_in_both_directions(): void
    {
        $a = $this->product(['name' => 'A', 'price' => 100]);
        $b = Product::factory()->variable()->create(['name' => 'B', 'status' => 'published']);
        $c = $this->product(['name' => 'C', 'purchase_mode' => 'inquiry', 'price' => 1]);
        ProductVariant::factory()->create(['product_id' => $b->id, 'price' => 200]);
        ProductVariant::factory()->create(['product_id' => $b->id, 'price' => 0, 'active' => false]);
        ProductVariant::factory()->create(['product_id' => $b->id, 'price' => 1, 'purchase_mode' => 'inquiry']);
        $this->get('/productos?orden=precio-asc')->assertViewHas('products', fn ($p) => $p->pluck('id')->all() === [$a->id, $b->id, $c->id]);
        $this->get('/productos?orden=precio-desc')->assertViewHas('products', fn ($p) => $p->pluck('id')->all() === [$b->id, $a->id, $c->id]);
        $this->get('/productos?orden=nombre-desc')->assertViewHas('products', fn ($p) => $p->pluck('id')->all() === [$c->id, $b->id, $a->id]);
    }

    public function test_relevance_prioritizes_exact_code_then_name_then_prefix(): void
    {
        $description = $this->product(['name' => 'A', 'description' => 'Taladro']);
        $prefix = $this->product(['name' => 'Taladro grande']);
        $exact = $this->product(['name' => 'Taladro']);
        $sku = $this->product(['name' => 'Z', 'sku' => 'TALADRO']);
        $this->get('/productos?q=taladro&orden=relevancia')->assertViewHas('products', fn ($p) => $p->pluck('id')->all() === [$sku->id, $exact->id, $prefix->id, $description->id]);
    }

    public function test_pagination_keeps_validated_filters_and_does_not_load_entire_catalog(): void
    {
        Product::factory()->count(15)->create(['name' => 'Cable', 'status' => 'published', 'sale_unit' => 'meter']);
        $this->get('/productos?q=cable&unidad=meter&orden=precio-asc&unexpected=secret')->assertOk()->assertViewHas('products', fn ($p) => $p->count() === 12 && $p->total() === 15 && str_contains($p->nextPageUrl(), 'unidad=meter') && ! str_contains($p->nextPageUrl(), 'unexpected'));
        $this->get('/productos?q=cable&unidad=meter&page=2')->assertViewHas('products', fn ($p) => $p->count() === 3);
    }

    public static function invalidInputs(): array
    {
        return array_map(fn (array $params): array => [$params], [['q' => ['bad']], ['q' => str_repeat('a', 121)], ['categoria' => ['bad']], ['categoria' => '../secret'], ['unidad' => 'bad'], ['modalidad' => 'bad'], ['tipo' => 'bad'], ['disponibilidad' => '1 OR 1=1'], ['orden' => 'name; DROP TABLE products'], ['page' => 0], ['page' => 100001], ['variante' => -1]]);
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_filters_return_friendly_422(array $params): void
    {
        $this->get('/productos?'.http_build_query($params))->assertStatus(422)->assertSee('Revisá tu búsqueda')->assertDontSee('SQLSTATE');
        $this->assertDatabaseCount('products', 0);
    }

    public function test_nonexistent_and_inactive_category_filters_return_404(): void
    {
        Category::factory()->create(['slug' => 'hidden', 'active' => false]);
        $this->get('/productos?categoria=missing')->assertNotFound();
        $this->get('/productos?categoria=hidden')->assertNotFound();
    }
}
