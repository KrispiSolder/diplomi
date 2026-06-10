<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        $popularCategoryIds = DB::table('order_items')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('category_product', 'category_product.product_id', '=', 'products.id')
            ->join('categories', 'categories.id', '=', 'category_product.category_id')
            ->where('categories.is_active', true)
            ->groupBy('categories.id')
            ->orderByRaw('SUM(order_items.quantity) DESC')
            ->limit(3)
            ->pluck('categories.id');

        if ($popularCategoryIds->isEmpty()) {
            $popularCategoryIds = Category::query()
                ->active()
                ->whereHas('products', function ($query) {
                    $query->where('quantity', '>', 0)
                        ->whereHas('stockStatusRef', fn ($q) => $q->where('code', 'in_stock'));
                })
                ->withCount(['products' => function ($query) {
                    $query->where('quantity', '>', 0)
                        ->whereHas('stockStatusRef', fn ($q) => $q->where('code', 'in_stock'));
                }])
                ->orderByDesc('products_count')
                ->limit(3)
                ->pluck('id');
        }

        $popularCategories = Category::query()
            ->whereIn('id', $popularCategoryIds)
            ->get()
            ->sortBy(fn (Category $category) => $popularCategoryIds->search($category->id))
            ->values();

        $products = Product::query()
            ->with(['stockStatusRef', 'images', 'categories'])
            ->withCount('orderItems as orders_count')
            ->whereHas('stockStatusRef', fn ($q) => $q->where('code', 'in_stock'))
            ->where('quantity', '>', 0)
            ->orderByDesc('orders_count')
            ->take(24)
            ->get();

        $favorites = Auth::check() ? Auth::user()->favorites()->pluck('product_id')->toArray() : [];

        return Inertia::render('Home', [
            'products' => $products,
            'categories' => $popularCategories,
            'favorites' => $favorites,
        ]);
    }
}
