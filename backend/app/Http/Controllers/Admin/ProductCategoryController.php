<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductCategoriesRequest;
use App\Models\Product;
use App\Services\Audit\ActivityLogService;
use App\Services\Catalog\CategoryManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProductCategoryController extends Controller
{
    public function edit(Product $product, CategoryManager $manager): View
    {
        Gate::authorize('update', $product);

        return view('admin.products.categories', ['product' => $product, 'tree' => $manager->tree(), 'selected' => $product->categories()->pluck('categories.id')->all()]);
    }

    public function update(ProductCategoriesRequest $request, Product $product): RedirectResponse
    {
        DB::transaction(function () use ($product, $request): void {
            Product::whereKey($product->id)->update(['updated_at' => now()]);
            $before = ['category_ids' => $product->categories()->orderBy('categories.id')->pluck('categories.id')->all()];
            $product->categories()->sync($request->validated('category_ids', []));
            app(ActivityLogService::class)->record('products', 'categories', $product, $before, ['category_ids' => $product->categories()->orderBy('categories.id')->pluck('categories.id')->all()]);
        }, 5);

        return back()->with('status', 'Categorías del producto guardadas.');
    }
}
