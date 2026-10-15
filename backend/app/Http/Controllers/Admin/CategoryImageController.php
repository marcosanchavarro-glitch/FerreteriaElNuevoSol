<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImageUploadRequest;
use App\Models\Category;
use App\Services\Catalog\CategoryImages;
use App\Services\Catalog\ImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CategoryImageController extends Controller
{
    public function store(ImageUploadRequest $request, Category $category, CategoryImages $images): RedirectResponse
    {
        $images->replace($category, $request->file('image'));

        return back()->with('status', 'Imagen de categoría actualizada.');
    }

    public function show(Category $category, ImageStorage $storage): StreamedResponse
    {
        Gate::authorize('update', $category);
        abort_if($category->image_path === null, 404);

        return $storage->response($category->image_path, 'categories/'.$category->id);
    }

    public function destroy(Category $category, CategoryImages $images): RedirectResponse
    {
        Gate::authorize('update', $category);
        $images->replace($category, null);

        return back()->with('status', 'Imagen de categoría eliminada.');
    }
}
