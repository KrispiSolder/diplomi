<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\PromoCodeController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\YandexGeocodeController;
use App\Http\Controllers\YooKassaPaymentController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');
Route::get('/product/{id}', [ProductController::class, 'show'])->name('product.show');

Route::post('/payments/yookassa/webhook', [YooKassaPaymentController::class, 'webhook'])
    ->name('payments.yookassa.webhook');

// Auth routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::get('/password-reset', [AuthController::class, 'showPasswordReset'])->name('password.reset');
Route::post('/password-reset', [AuthController::class, 'passwordReset'])->name('password.reset.store');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/auth/yandex/redirect', [AuthController::class, 'redirectToYandex'])->name('login.yandex');
Route::get('/auth/yandex/callback', [AuthController::class, 'handleYandexCallback']);

// User routes
Route::middleware('auth')->group(function () {
    Route::get('/cabinet', [UserController::class, 'cabinet'])->name('cabinet');
    Route::patch('/profile', [UserController::class, 'updateProfile'])->name('profile.update');

    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites');

    Route::get('/cart', [CartController::class, 'index'])->name('cart');
    Route::post('/cart/add', [CartController::class, 'addToCart'])->name('cart.add');
    Route::patch('/cart/{id}/quantity', [CartController::class, 'updateQuantity'])->name('cart.update');
    Route::delete('/cart/{id}', [CartController::class, 'removeFromCart'])->name('cart.remove');
    Route::post('/cart/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
    Route::post('/cart/promo', [CartController::class, 'applyPromo'])->name('cart.promo.apply');
    Route::delete('/cart/promo', [CartController::class, 'clearPromo'])->name('cart.promo.clear');

    Route::get('/payment/success', [YooKassaPaymentController::class, 'success'])->name('payment.success');
    Route::get('/payment/cancel', [YooKassaPaymentController::class, 'cancel'])->name('payment.cancel');

    Route::post('/favorites/{productId}', [FavoriteController::class, 'toggle'])->name('favorite.toggle');

    Route::post('/reviews', [ReviewController::class, 'store'])->name('review.store');

    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    Route::get('/yandex/reverse-geocode', [YandexGeocodeController::class, 'reverse'])
        ->middleware('throttle:120,1')
        ->name('yandex.reverse-geocode');
    Route::get('/yandex/forward-geocode', [YandexGeocodeController::class, 'forward'])
        ->middleware('throttle:120,1')
        ->name('yandex.forward-geocode');
});

// Admin routes
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/products', [AdminController::class, 'products'])->name('admin.products');
    Route::get('/reviews', [AdminController::class, 'reviews'])->name('admin.reviews');
    Route::get('/orders/export', [AdminController::class, 'exportOrders'])->name('admin.orders.export');
    Route::get('/orders/low-stock-pdf', [AdminController::class, 'lowStockPdf'])->name('admin.orders.low-stock-pdf');
    Route::get('/orders', [AdminController::class, 'orders'])->name('admin.orders');

    Route::get('/categories', [CategoryController::class, 'index'])->name('admin.categories');
    Route::post('/categories', [CategoryController::class, 'store'])->name('admin.categories.store');
    Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('admin.categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('admin.categories.destroy');

    Route::get('/promo-codes', [PromoCodeController::class, 'index'])->name('admin.promo-codes');
    Route::post('/promo-codes', [PromoCodeController::class, 'store'])->name('admin.promo-codes.store');
    Route::patch('/promo-codes/{promoCode}', [PromoCodeController::class, 'update'])->name('admin.promo-codes.update');
    Route::delete('/promo-codes/{promoCode}', [PromoCodeController::class, 'destroy'])->name('admin.promo-codes.destroy');

    Route::post('/products', [AdminController::class, 'storeProduct'])->name('admin.product.store');
    Route::patch('/products/{id}', [AdminController::class, 'updateProduct'])->name('admin.product.update');
    Route::delete('/products/{id}', [AdminController::class, 'deleteProduct'])->name('admin.product.delete');

    Route::patch('/reviews/{id}/approve', [AdminController::class, 'approveReview'])->name('admin.review.approve');
    Route::delete('/reviews/{id}', [AdminController::class, 'rejectReview'])->name('admin.review.reject');

    Route::patch('/orders/{id}/status', [AdminController::class, 'updateOrderStatus'])->name('admin.order.status');
    Route::patch('/orders/{id}/cancel', [AdminController::class, 'cancelOrder'])->name('admin.order.cancel');
});
