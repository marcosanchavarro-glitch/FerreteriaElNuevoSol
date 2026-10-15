<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\VariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Catalog\VariantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class VariantController extends Controller
{
    public function index(Product $product): View
    {
        Gate::authorize('view', $product);

        return view('admin.variants.index', ['product' => $product, 'variants' => $product->variants()->orderBy('id')->paginate(20)]);
    }

    public function create(Product $product): View
    {
        Gate::authorize('create', [ProductVariant::class, $product]);
        $variant = (new ProductVariant(['active' => false]))->setRelation('product', $product);

        return view('admin.variants.form', compact('product', 'variant'));
    }

    public function store(VariantRequest $request, Product $product, VariantManager $manager): RedirectResponse
    {
        $variant = $manager->save($product, null, $request->variantData());

        return redirect()->route('admin.products.variants.show', [$product, $variant])->with('status', 'Variante creada.');
    }

    public function show(Product $product, ProductVariant $variant): View
    {
        Gate::authorize('view', $variant);

        return view('admin.variants.show', compact('product', 'variant'));
    }

    public function edit(Product $product, ProductVariant $variant): View
    {
        Gate::authorize('update', $variant);

        return view('admin.variants.form', compact('product', 'variant'));
    }

    public function update(VariantRequest $request, Product $product, ProductVariant $variant, VariantManager $manager): RedirectResponse
    {
        $manager->save($product, $variant, $request->variantData());

        return redirect()->route('admin.products.variants.show', [$product, $variant])->with('status', 'Variante actualizada.');
    }
}
