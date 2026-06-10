<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'content' => 'required|string|max:1000',
        ]);

        $existingReview = Review::where('product_id', $validated['product_id'])
            ->where('user_id', Auth::id())
            ->first();

        if ($existingReview) {
            return back()->withErrors(['content' => 'Вы уже оставили отзыв на этот товар']);
        }

        $reviewableIds = Order::reviewableStatusIds();
        $hasPurchased = $reviewableIds !== [] && Auth::user()->orders()
            ->whereIn('order_status_id', $reviewableIds)
            ->whereHas('items', function ($q) use ($validated) {
                $q->where('product_id', $validated['product_id']);
            })
            ->exists();

        if (! $hasPurchased) {
            return back()->withErrors(['content' => 'Оставить отзыв можно только после выполнения заказа с этим товаром']);
        }

        Review::create([
            'product_id' => $validated['product_id'],
            'user_id' => Auth::id(),
            'rating' => $validated['rating'],
            'comment' => $validated['content'],
            'approved' => false,
        ]);

        return back()->with('success', 'Отзыв отправлен на модерацию');
    }
}
