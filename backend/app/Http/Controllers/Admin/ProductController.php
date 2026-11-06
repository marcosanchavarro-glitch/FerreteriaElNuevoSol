<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\PurchaseMode;
use App\Enums\SaleUnit;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\Audit\ActivityLogService;
use App\Services\Catalog\CategoryManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Product::class);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'mode' => ['nullable', 'in:inquiry,calculated'],
            'status' => ['nullable', 'in:draft,published,private'],
            'availability' => ['nullable', 'in:available,empty,unknown'],
            'featured' => ['nullable', 'in:0,1'],
            'sort' => ['nullable', 'in:newest,name,oldest'],
            'view' => ['nullable', 'in:cards,table'],
        ]);
        $query = trim($filters['q'] ?? '');
        $products = Product::query()
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($builder) use ($query) {
                    $builder->where('name', 'like', '%'.$query.'%')->orWhere('sku', 'like', '%'.$query.'%');
                });
            })
            ->when($filters['category'] ?? null, fn ($q, $id) => $q->whereHas('categories', fn ($q) => $q->where('categories.id', $id)))
            ->when($filters['mode'] ?? null, fn ($q, $mode) => $q->where('purchase_mode', $mode))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when(isset($filters['featured']), fn ($q) => $q->where('featured', $filters['featured']))
            ->when($filters['availability'] ?? null, function ($q, $availability) {
                $stock = function ($q) use ($availability) {
                    if ($availability === 'unknown') {
                        $q->whereNull('stock');
                    } else {
                        $q->whereNotNull('stock')->where('stock', $availability === 'available' ? '>' : '<=', 0);
                    }
                };
                $q->where(function ($q) use ($stock) {
                    $q->where(fn ($q) => $q->where('product_type', 'simple')->where($stock))
                        ->orWhere(fn ($q) => $q->where('product_type', 'variable')->whereHas('variants', fn ($q) => $q->where('active', true)->where($stock)));
                });
            })
            ->with(['categories:id,name', 'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id')->limit(1)])
            ->withCount('variants')
            ->when(($filters['sort'] ?? '') === 'name', fn ($q) => $q->orderBy('name')->orderBy('id'),
                fn ($q) => $q->orderBy('id', ($filters['sort'] ?? '') === 'oldest' ? 'asc' : 'desc'))
            ->paginate(15)->withQueryString();

        return view('admin.products.index', [
            'products' => $products, 'query' => $query, 'filters' => $filters,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function quickUpdate(Request $request, Product $product): RedirectResponse
    {
        Gate::authorize('update', $product);
        $data = $request->validate(['action' => ['required', 'in:featured,visibility']]);
        DB::transaction(function () use ($product, $data): void {
            Product::whereKey($product->id)->update(['updated_at' => now()]);
            $product->refresh();
            Gate::authorize('update', $product);
            $before = app(ActivityLogService::class)->snapshot($product);
            if ($data['action'] === 'featured') {
                $product->featured = ! $product->featured;
            } else {
                $status = $product->status === ProductStatus::Published ? ProductStatus::Private : ProductStatus::Published;
                if ($status === ProductStatus::Published && $product->product_type === ProductType::Simple && $product->purchase_mode === PurchaseMode::Calculated && $product->price === null) {
                    throw ValidationException::withMessages(['status' => 'Para publicar un producto calculable se necesita precio.']);
                }
                $product->status = $status;
            }
            $product->save();
            app(ActivityLogService::class)->changed($product, $before);
        }, 5);

        return back()->with('status', 'Producto actualizado.');
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

        return redirect()->route($request->boolean('_workspace') ? 'admin.products.edit' : 'admin.products.show', $product)->with('status', 'Producto creado.');
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
        $product->load(['categories', 'images' => fn ($q) => $q->orderBy('sort_order')->orderBy('id')]);

        return view('admin.products.form', [
            'product' => $product,
            'tree' => app(CategoryManager::class)->tree(),
            'selected' => $product->categories->modelKeys(),
            'images' => $product->images,
            'variants' => $product->product_type === ProductType::Variable ? $product->variants()->orderBy('id')->paginate(20, ['*'], 'variants_page') : null,
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse|JsonResponse
    {
        DB::transaction(function () use ($product, $request): void {
            Product::whereKey($product->id)->update(['updated_at' => now()]);
            $product->refresh();
            $request->validateResolved();
            $before = app(ActivityLogService::class)->snapshot($product);
            $product->update($request->productData());
            app(ActivityLogService::class)->changed($product, $before);
        }, 5);

        if ($request->expectsJson()) {
            return response()->json(['saved' => true]);
        }

        return redirect()->route($request->boolean('_workspace') ? 'admin.products.edit' : 'admin.products.show', $product)->with('status', 'Producto actualizado.');
    }
}
