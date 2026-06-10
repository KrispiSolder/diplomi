<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\ServiceProvider;

class InertiaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Inertia::share([
            'auth.user' => fn() => Auth::user(),
        ]);
    }
}
