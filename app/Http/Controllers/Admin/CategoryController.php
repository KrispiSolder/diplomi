<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with('parent')->orderBy('name')->paginate(30);
        $parentOptions = Category::orderBy('name')->get(['id', 'name', 'parent_id']);

        return Inertia::render('Admin/Categories', [
            'categories' => $categories,
            'parentOptions' => $parentOptions,
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'slug' => $request->input('slug') ?: null,
            'parent_id' => $request->input('parent_id') ?: null,
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean',
        ]);

        $slug = $validated['slug'] ?? $this->makeUniqueSlug($validated['name']);

        Category::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true,
        ]);

        return back();
    }

    public function update(Request $request, Category $category)
    {
        $request->merge([
            'slug' => $request->input('slug') ?: null,
            'parent_id' => $request->input('parent_id') ?: null,
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug,'.$category->id,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean',
        ]);

        if (isset($validated['parent_id']) && (int) $validated['parent_id'] === (int) $category->id) {
            return back()->withErrors(['parent_id' => 'Категория не может быть родителем самой себя']);
        }

        $slug = $validated['slug'] ?? $category->slug;
        if (($validated['slug'] ?? null) === null && $category->name !== $validated['name']) {
            $slug = $this->makeUniqueSlug($validated['name'], $category->id);
        }

        $category->update([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : $category->is_active,
        ]);

        return back();
    }

    public function destroy(Category $category)
    {
        $hasPivot = DB::table('category_product')->where('category_id', $category->id)->exists();

        if ($hasPivot) {
            return back()->withErrors(['category' => 'Нельзя удалить категорию с привязанными товарами']);
        }

        if ($category->children()->exists()) {
            return back()->withErrors(['category' => 'Сначала удалите или перенесите дочерние категории']);
        }

        $category->delete();

        return back();
    }

    private function makeUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug(Str::ascii($name));
        if ($base === '') {
            $base = 'category';
        }

        $slug = $base;
        $i = 1;
        while (
            Category::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
