<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FavoriteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        $favorites = $user->favorites()->with('product')->get();

        return Inertia::render('Favorites', [
            'favorites' => $favorites,
        ]);
    }

    public function toggle($productId)
    {
        $user = Auth::user();
        if ($user->isAdmin()) {
            return back()->withErrors(['favorite' => 'Не доступно администратору']);
        }

        if (! Product::whereKey($productId)->exists()) {
            abort(404);
        }

        $favorite = $user->favorites()->where('product_id', $productId)->first();

        if ($favorite) {
            $favorite->delete();
        } else {
            $user->favorites()->create(['product_id' => $productId]);
        }

        return back();
    }
}
