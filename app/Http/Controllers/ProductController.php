<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function show(Request $request, $id)
    {
        $product = Product::with(['images', 'categories', 'stockStatusRef', 'careDifficultyRef', 'productSizeRef', 'ageGroupRef'])->findOrFail($id);
        $reviews = Review::where('product_id', $id)
            ->where('approved', true)
            ->with('user')
            ->get();
        $averageRating = Review::where('product_id', $id)
            ->where('approved', true)
            ->avg('rating') ?? 0;
        
        $isFavorite = Auth::check() ? Auth::user()->favorites()->where('product_id', $id)->exists() : false;
        $cartQuantity = 0;
        $cartItem = null;
        if (Auth::check()) {
            $cartItemModel = Auth::user()->cartItems()->where('product_id', $id)->first();
            $cartQuantity = (int) ($cartItemModel?->quantity ?? 0);
            if ($cartItemModel) {
                $cartItem = ['id' => $cartItemModel->id, 'quantity' => $cartItemModel->quantity];
            }
        }

        $reviewableIds = Order::reviewableStatusIds();
        $userHasOrdered = Auth::check() && $reviewableIds !== []
            ? Auth::user()->orders()
                ->whereIn('order_status_id', $reviewableIds)
                ->whereHas('items', function ($q) use ($id) {
                    $q->where('product_id', $id);
                })
                ->exists()
            : false;

        $catalogBackUrl = '/catalog';
        $from = $request->query('from');
        if (is_string($from) && str_starts_with($from, '/catalog')) {
            $catalogBackUrl = $from;
        }

        return Inertia::render('ProductDetail', [
            'product' => $product,
            'reviews' => $reviews,
            'averageRating' => round($averageRating, 1),
            'isFavorite' => $isFavorite,
            'userCanReview' => $userHasOrdered,
            'cartQuantity' => $cartQuantity,
            'cartItem' => $cartItem,
            'catalogBackUrl' => $catalogBackUrl,
            'categoryPromo' => PromoCode::bannerForProduct($product),
        ]);
    }
}
