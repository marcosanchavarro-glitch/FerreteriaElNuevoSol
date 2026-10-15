<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Services\Catalog\CategoryManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(CategoryManager $manager): View
    {
        Gate::authorize('viewAny', Category::class);

        return view('admin.categories.index', ['tree' => $manager->tree()]);
    }

    public function create(CategoryManager $manager): View
    {
        Gate::authorize('create', Category::class);

        return view('admin.categories.form', ['category' => new Category(['active' => true, 'featured' => false, 'sort_order' => 0]), 'tree' => $manager->tree()]);
    }

    public function store(CategoryRequest $request, CategoryManager $manager): RedirectResponse
    {
        $category = $manager->save(new Category, $request->validated());

        return redirect()->route('admin.categories.edit', $category)->with('status', 'Categoría creada.');
    }

    public function edit(Category $category, CategoryManager $manager): View
    {
        Gate::authorize('update', $category);
        $category->loadCount('products');

        return view('admin.categories.form', ['category' => $category, 'tree' => $manager->tree()]);
    }

    public function update(CategoryRequest $request, Category $category, CategoryManager $manager): RedirectResponse
    {
        $manager->save($category, $request->validated());

        return redirect()->route('admin.categories.edit', $category)->with('status', 'Categoría actualizada.');
    }
}
