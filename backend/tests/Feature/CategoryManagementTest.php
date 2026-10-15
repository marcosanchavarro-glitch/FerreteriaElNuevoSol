<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Catalog\CategoryManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private function data(array $changes = []): array
    {
        return array_merge(['name' => 'Herramientas', 'slug' => 'herramientas', 'parent_id' => null, 'active' => '1', 'featured' => '0', 'sort_order' => '0'], $changes);
    }

    public static function staff(): array
    {
        return [['admin'], ['seller']];
    }

    #[DataProvider('staff')]
    public function test_staff_creates_edits_orders_features_and_deactivates_categories(string $role): void
    {
        $this->actingAs(User::factory()->create(['role' => $role]))->get('/admin/categories')->assertOk();
        $this->get('/admin/categories/create')->assertOk();
        $this->post('/admin/categories', $this->data())->assertRedirect();
        $category = Category::sole();
        $this->assertFalse($category->featured);
        $child = Category::factory()->create(['parent_id' => $category->id]);
        $product = Product::factory()->create();
        $product->categories()->attach($category);
        $this->get('/admin/categories/'.$category->id.'/edit')->assertOk()->assertSee('1 productos');
        $this->put('/admin/categories/'.$category->id, $this->data(['name' => 'Máquinas', 'active' => '0', 'featured' => '1', 'sort_order' => '20']))->assertRedirect();
        $category->refresh();
        $this->assertFalse($category->active);
        $this->assertTrue($category->featured);
        $this->assertSame(20, $category->sort_order);
        $this->assertSame('Máquinas', $category->name);
        $this->assertTrue($child->fresh()->active);
        $this->assertSame($category->id, $child->fresh()->parent_id);
        $this->assertCount(1, $product->fresh()->categories);
        $this->delete('/admin/categories/'.$category->id)->assertStatus(405);
        $this->assertModelExists($product);
    }

    public function test_deep_hierarchy_is_displayed_without_two_level_limit_and_sorted_among_siblings(): void
    {
        $root = Category::factory()->create(['name' => 'Raíz']);
        $parent = $root;
        for ($depth = 1; $depth <= 6; $depth++) {
            $parent = Category::factory()->create(['parent_id' => $parent->id, 'name' => 'Nivel '.$depth]);
        }
        $last = Category::factory()->create(['parent_id' => $root->id, 'name' => 'Último', 'sort_order' => 10]);
        $tree = app(CategoryManager::class)->tree();
        $this->assertCount(8, $tree);
        $this->assertSame(6, $tree[6]['depth']);
        $this->assertSame($last->id, $tree[7]['category']->id);
        $this->actingAs(User::factory()->admin()->create())->get('/admin/categories')->assertOk()->assertSee('Nivel 6')->assertSee('Último');
    }

    public function test_self_parent_and_descendant_cycles_are_rejected_and_old_tree_retained(): void
    {
        $a = Category::factory()->create();
        $b = Category::factory()->create(['parent_id' => $a->id]);
        $c = Category::factory()->create(['parent_id' => $b->id]);
        $this->actingAs(User::factory()->admin()->create());
        foreach ([$a->id, $b->id, $c->id] as $parent) {
            $this->putJson('/admin/categories/'.$a->id, $this->data(['parent_id' => $parent]))->assertUnprocessable()->assertJsonValidationErrors('parent_id');
        }
        $this->assertNull($a->fresh()->parent_id);
        $this->assertSame($a->id, $b->fresh()->parent_id);
        $this->put('/admin/categories/'.$c->id, $this->data(['parent_id' => $a->id]))->assertRedirect();
        $this->assertSame($a->id, $c->fresh()->parent_id);
    }

    #[DataProvider('staff')]
    public function test_staff_assigns_multiple_categories_and_can_clear_associations(string $role): void
    {
        $product = Product::factory()->create(['external_id' => 'SYNC']);
        $categories = Category::factory()->count(3)->create();
        $url = '/admin/products/'.$product->id.'/categories';
        $this->actingAs(User::factory()->create(['role' => $role]))->get($url)->assertOk();
        $this->put($url, ['category_ids' => [$categories[0]->id, $categories[2]->id]])->assertRedirect();
        $this->assertEqualsCanonicalizing([$categories[0]->id, $categories[2]->id], $product->categories()->pluck('categories.id')->all());
        $this->put($url, ['category_ids' => [$categories[1]->id]])->assertRedirect();
        $this->assertSame([$categories[1]->id], $product->categories()->pluck('categories.id')->all());
        $this->put($url, [])->assertRedirect();
        $this->assertSame(0, $product->categories()->count());
        $this->assertDatabaseCount('products', 1);
    }

    public function test_invalid_assignment_does_not_remove_previous_associations(): void
    {
        $product = Product::factory()->create();
        $category = Category::factory()->create();
        $product->categories()->attach($category);
        $url = '/admin/products/'.$product->id.'/categories';
        $this->actingAs(User::factory()->admin()->create())->putJson($url, ['category_ids' => [$category->id, 9999]])->assertUnprocessable();
        $this->putJson($url, ['category_ids' => [$category->id, $category->id]])->assertUnprocessable();
        $this->putJson($url, ['category_ids' => 'invalid'])->assertUnprocessable();
        $this->assertSame([$category->id], $product->categories()->pluck('categories.id')->all());
    }

    public static function invalidCategoryData(): array
    {
        return [['parent_id', 9999], ['parent_id', 'text'], ['slug', 'BAD slug'], ['sort_order', -1], ['sort_order', '1.5'], ['active', 'yes'], ['featured', 'yes'], ['name', '']];
    }

    #[DataProvider('invalidCategoryData')]
    public function test_category_validation(string $field, mixed $value): void
    {
        $this->actingAs(User::factory()->admin()->create())->postJson('/admin/categories', $this->data([$field => $value]))->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertDatabaseCount('categories', 0);
    }

    public function test_duplicate_slug_and_arbitrary_image_paths_are_blocked(): void
    {
        Category::factory()->create(['slug' => 'herramientas']);
        $this->actingAs(User::factory()->admin()->create())->postJson('/admin/categories', $this->data())->assertUnprocessable()->assertJsonValidationErrors('slug');
        $this->post('/admin/categories', $this->data(['slug' => 'safe', 'image_path' => '../../secret']))->assertForbidden();
        $this->assertDatabaseCount('categories', 1);
    }

    public function test_customer_cannot_manage_categories_or_product_associations(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create();
        $this->get('/admin/categories')->assertRedirect('/login');
        $this->actingAs(User::factory()->create());
        foreach (['/admin/categories', '/admin/categories/create', '/admin/categories/'.$category->id.'/edit', '/admin/products/'.$product->id.'/categories'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->post('/admin/categories', $this->data())->assertForbidden();
        $this->put('/admin/categories/'.$category->id, $this->data())->assertForbidden();
        $this->put('/admin/products/'.$product->id.'/categories', ['category_ids' => [$category->id]])->assertForbidden();
        $this->assertSame(0, $product->categories()->count());
    }

    public function test_invalid_assignment_form_can_render_errors_without_losing_existing_selection(): void
    {
        $product = Product::factory()->create();
        $category = Category::factory()->create();
        $product->categories()->attach($category);
        $url = '/admin/products/'.$product->id.'/categories';
        $this->actingAs(User::factory()->admin()->create())->from($url)->put($url, ['category_ids' => 'malformed'])->assertRedirect($url)->assertSessionHasErrors('category_ids');
        $this->get($url)->assertOk()->assertSee($category->name);
        $this->assertSame([$category->id], $product->categories()->pluck('categories.id')->all());
    }
}
