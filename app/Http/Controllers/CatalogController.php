<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\PromoCode;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $categorySlug = $request->get('category', 'all');
        $sortBy = $request->get('sort', 'popular');
        $search = $request->get('search');

        $care = $this->csvOrArray($request->get('care_difficulty'));
        $sizes = $this->csvOrArray($request->get('size'));
        $ages = $this->csvOrArray($request->get('age_group'));

        $cancelledId = Order::cancelledStatusId();
        $query = Product::query()
            ->with(['categories', 'stockStatusRef', 'careDifficultyRef', 'productSizeRef', 'ageGroupRef', 'images'])
            ->withCount([
                'orderItems as orders_count' => function ($q) use ($cancelledId) {
                    $q->whereHas('order', function ($orderQuery) use ($cancelledId) {
                        if ($cancelledId) {
                            $orderQuery->where('order_status_id', '!=', $cancelledId);
                        }
                    });
                },
            ]);

        if ($categorySlug !== 'all') {
            $cat = Category::where('slug', $categorySlug)->first();
            if ($cat) {
                $ids = $cat->subtreeCategoryIds()->all();
                $query->whereHas('categories', fn ($qq) => $qq->whereIn('categories.id', $ids));
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        if ($care->isNotEmpty()) {
            $query->whereHas('careDifficultyRef', fn ($q) => $q->whereIn('code', $care->all()));
        }
        if ($sizes->isNotEmpty()) {
            $query->whereHas('productSizeRef', fn ($q) => $q->whereIn('code', $sizes->all()));
        }
        if ($ages->isNotEmpty()) {
            $query->whereHas('ageGroupRef', fn ($q) => $q->whereIn('code', $ages->all()));
        }

        match ($sortBy) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'name' => $query->orderBy('name', 'asc'),
            default => $query->orderByDesc('orders_count')->orderBy('name'),
        };

        $products = $query->paginate(12)->withQueryString();

        $categoryTree = $this->buildCategoryTree();
        $categories = Category::active()->orderBy('name')->get(['id', 'name', 'slug', 'parent_id']);

        $favorites = Auth::check() ? Auth::user()->favorites()->pluck('product_id')->toArray() : [];

        return Inertia::render('Catalog', [
            'products' => $products,
            'categories' => $categories,
            'categoryTree' => $categoryTree,
            'selectedCategory' => $categorySlug,
            'search' => $search,
            'favorites' => $favorites,
            'filters' => [
                'care_difficulty' => $care->values()->all(),
                'size' => $sizes->values()->all(),
                'age_group' => $ages->values()->all(),
            ],
            'sort' => $sortBy,
            'categoryPromo' => PromoCode::bannerForCatalogCategory($categorySlug),
        ]);
    }

    private function csvOrArray(mixed $value): Collection
    {
        if ($value === null || $value === '') {
            return collect();
        }
        if (is_array($value)) {
            return collect($value)->filter(fn ($v) => $v !== null && $v !== '');
        }

        return collect(explode(',', (string) $value))->map(fn ($s) => trim($s))->filter();
    }

    private function buildCategoryTree(?int $parentId = null): array
    {
        return Category::active()
            ->where('parent_id', $parentId)
            ->orderBy('name')
            ->get()
            ->map(function (Category $c) {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                    'product_count' => $this->distinctProductCountForSubtree($c),
                    'children' => $this->buildCategoryTree($c->id),
                ];
            })
            ->values()
            ->all();
    }

    private function distinctProductCountForSubtree(Category $root): int
    {
        $ids = $root->subtreeCategoryIds()->all();

        return DB::table('category_product')->whereIn('category_id', $ids)->distinct()->count('product_id');
    }
}
