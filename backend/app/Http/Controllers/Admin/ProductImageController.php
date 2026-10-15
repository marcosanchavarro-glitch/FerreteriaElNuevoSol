<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImageUpdateRequest;
use App\Http\Requests\ImageUploadRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Audit\ActivityLogService;
use App\Services\Catalog\ImageStorage;
use App\Services\Catalog\ProductGallery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductImageController extends Controller
{
    public function index(Product $product): View
    {
        Gate::authorize('update', $product);

        return view('admin.products.images', ['product' => $product, 'images' => $product->images()->orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function store(ImageUploadRequest $request, Product $product, ProductGallery $gallery): RedirectResponse
    {
        $gallery->upload($product, $request->file('image'), $request->validated('alt_text'));

        return back()->with('status', 'Imagen incorporada.');
    }

    public function show(Product $product, ProductImage $image, ImageStorage $storage): StreamedResponse
    {
        Gate::authorize('view', $product);

        return $storage->response($image->path, 'products/'.$product->id);
    }

    public function update(ImageUpdateRequest $request, Product $product, ProductImage $image): RedirectResponse
    {
        DB::transaction(function () use ($request, $product, $image): void {
            Product::whereKey($product->id)->update(['updated_at' => now()]);
            $audit = app(ActivityLogService::class);
            $before = $audit->gallery($product);
            $image->update(['alt_text' => $request->validated('alt_text')]);
            $audit->record('images', 'updated', $product->id, $before, $audit->gallery($product));
        });

        return back()->with('status', 'Texto alternativo guardado.');
    }

    public function primary(Product $product, ProductImage $image, ProductGallery $gallery): RedirectResponse
    {
        Gate::authorize('update', $product);
        $gallery->primary($product, $image);

        return back()->with('status', 'Imagen principal actualizada.');
    }

    public function move(Request $request, Product $product, ProductImage $image, ProductGallery $gallery): RedirectResponse
    {
        Gate::authorize('update', $product);
        $data = $request->validate(['direction' => ['required', 'in:up,down']]);
        $gallery->move($product, $image, $data['direction']);

        return back()->with('status', 'Orden actualizado.');
    }

    public function destroy(Product $product, ProductImage $image, ProductGallery $gallery): RedirectResponse
    {
        Gate::authorize('update', $product);
        $gallery->delete($product, $image);

        return back()->with('status', 'Imagen eliminada.');
    }
}
