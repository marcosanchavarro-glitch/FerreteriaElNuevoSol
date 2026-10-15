<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Http\Requests\CatalogRequest;
use App\Services\Content\Homepage;
use App\Services\Storefront\CatalogQuery;
use App\Services\Storefront\ProductPresentation;
use App\Services\Storefront\PublicCategories;
use App\Services\Storefront\PublicImages;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __construct(private CatalogQuery $query, private PublicCategories $categories, private ProductPresentation $presentation, private PublicImages $images) {}

    private function common(): array
    {
        return ['categoryRows' => $this->categories->rows(), 'navCategories' => $this->categories->all()->whereNull('parent_id'), 'presenter' => $this->presentation, 'publicImages' => $this->images];
    }

    public function home(): View
    {
        return view('storefront.home', $this->common() + app(Homepage::class)->data($this->query, $this->categories));
    }

    public function index(CatalogRequest $request, ?string $slug = null): View
    {
        $filters = array_filter($request->validated(), fn ($value) => $value !== null && $value !== '');
        $category = $slug ? $this->categories->find($slug) : null;
        $branch = $category ? $this->categories->descendants($category) : null;
        $filterCategoryRows = array_filter($this->categories->rows(), fn (array $row): bool => $branch === null || in_array($row['category']->id, $branch, true));
        if (! empty($filters['categoria'])) {
            $filterBranch = $this->categories->descendants($this->categories->find($filters['categoria']));
            $branch = $branch === null ? $filterBranch : array_values(array_intersect($branch, $filterBranch));
        }
        $products = $this->query->catalog($filters, $this->categories, $branch)->paginate(12)->appends($filters);

        return view('storefront.catalog', $this->common() + compact('products', 'filters', 'category', 'filterCategoryRows') + ['ancestors' => $category ? $this->categories->ancestors($category) : [], 'children' => $category ? $this->categories->all()->where('parent_id', $category->id) : collect()]);
    }

    public function show(CatalogRequest $request, string $slug): View
    {
        $product = $this->query->base($this->categories->all()->keys()->all())->where('slug', $slug)->firstOrFail();
        $selected = null;
        if ($request->filled('variante')) {
            $selected = $product->variants->firstWhere('id', (int) $request->validated('variante'));
            abort_unless($selected, 404);
        } elseif ($product->product_type === ProductType::Variable) {
            $selected = $product->variants->first();
        }
        $offers = $product->variants->map(fn ($variant): array => $this->presentation->offer($product, $variant));
        $offer = $selected || $product->product_type === ProductType::Simple ? $this->presentation->offer($product, $selected) : null;
        $related = $this->query->base($this->categories->all()->keys()->all())->where('id', '!=', $product->id)->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $product->categories->pluck('id')))->orderByDesc('featured')->orderBy('id')->limit(3)->get();

        return view('storefront.product', $this->common() + compact('product', 'selected', 'offer', 'offers', 'related'));
    }
}
