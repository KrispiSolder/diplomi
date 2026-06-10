<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use App\Socialite\YandexOAuthProvider;
use Laravel\Socialite\Facades\Socialite;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Регистрируем провайдер для Socialite (Яндекс)
        Socialite::extend('yandex', function ($app) {
            $config = $app['config']['services.yandex'];
            return Socialite::buildProvider(YandexOAuthProvider::class, $config);
        });
    }
}
