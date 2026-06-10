<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function cabinet()
    {
        $user = Auth::user();
        $orders = $user->orders()
            ->with(['items.product.images', 'orderStatusRef', 'paymentMethodRef', 'paymentStatusRef'])
            ->latest()
            ->get();

        return Inertia::render('UserCabinet', [
            'user' => $user,
            'orders' => $orders,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $rules = [
            'name' => 'required|string|max:255',
        ];

        if (! $user->isAdmin()) {
            $rules['delivery_address'] = 'required|string|max:2000';
        } else {
            $rules['delivery_address'] = 'nullable|string|max:2000';
        }

        $validated = $request->validate($rules);

        $user->update($validated);

        return back();
    }
}
