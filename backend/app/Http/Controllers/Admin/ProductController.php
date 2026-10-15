<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\PurchaseMode;
use App\Enums\SaleUnit;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Services\Audit\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Product::class);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $query = trim($filters['q'] ?? '');
        $products = Product::query()->when($query !== '', function ($builder) use ($query) {
            $builder->where(function ($builder) use ($query) {
                $builder->where('name', 'like', '%'.$query.'%')->orWhere('sku', 'like', '%'.$query.'%');
            });
        })->withCount('variants')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('admin.products.index', ['products' => $products, 'query' => $query]);
    }

    public function create(): View
    {
        Gate::authorize('create', Product::class);
        $product = new Product(['product_type' => ProductType::Simple, 'sale_unit' => SaleUnit::Unit, 'purchase_mode' => PurchaseMode::Inquiry, 'status' => ProductStatus::Draft, 'featured' => false]);

        return view('admin.products.form', compact('product'));
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = DB::transaction(function () use ($request): Product {
            $product = Product::create($request->productData());
            app(ActivityLogService::class)->changed($product, [], 'created');

            return $product;
        });

        return redirect()->route('admin.products.show', $product)->with('status', 'Producto creado.');
    }

    public function show(Product $product): View
    {
        Gate::authorize('view', $product);
        $product->load('categories')->loadCount('variants');

        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        Gate::authorize('update', $product);

        return view('admin.products.form', compact('product'));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        DB::transaction(function () use ($product, $request): void {
            Product::whereKey($product->id)->update(['updated_at' => now()]);
            $product->refresh();
            $request->validateResolved();
            $before = app(ActivityLogService::class)->snapshot($product);
            $product->update($request->productData());
            app(ActivityLogService::class)->changed($product, $before);
        }, 5);

        return redirect()->route('admin.products.show', $product)->with('status', 'Producto actualizado.');
    }
}
