<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PromoCode;
use App\Services\CheckoutService;
use App\Services\ProductStockService;
use App\Services\PromoCodeService;
use App\Services\PromoCodeValidator;
use App\Services\YooKassaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CartController extends Controller
{
    public function __construct(
        private ProductStockService $stock,
        private CheckoutService $checkout,
        private YooKassaService $yookassa,
        private PromoCodeService $promoCodes,
    ) {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        $this->pruneOrphanCartItems($user);
        $cartItems = $this->checkout->filterCartItemsWithProduct(
            $user->cartItems()
                ->with(['product.stockStatusRef', 'product.images', 'product.categories'])
                ->get()
        );
        $favorites = $user->favorites()->pluck('product_id')->toArray();
        $promo = $this->sanitizeSessionPromo($user, session('promo'));

        return Inertia::render('Cart', [
            'cartItems' => $cartItems,
            'favorites' => $favorites,
            'user' => $user,
            'yookassaEnabled' => $this->yookassa->isConfigured(),
            'promo' => $promo,
            'orderPercentOffers' => $this->promoCodes->orderPercentOffersForCart($user, $cartItems),
        ]);
    }

    public function applyPromo(Request $request)
    {
        if ($response = $this->blockAdmin()) {
            return $response;
        }

        $validated = $request->validate([
            'promo_code' => 'required|string|max:64',
        ]);

        $code = PromoCodeValidator::normalizeCode($validated['promo_code']);

        try {
            PromoCodeValidator::validateCodeFormat($code, 'promo_code');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('promo', null);
        }

        $existing = session('promo');
        if ($existing && PromoCodeValidator::normalizeCode((string) ($existing['code'] ?? '')) !== $code) {
            return back()->withErrors([
                'promo_code' => 'В корзине уже применён другой промокод. Сначала нажмите «убрать».',
            ]);
        }

        $user = Auth::user();
        $this->pruneOrphanCartItems($user);
        $cartItems = $this->checkout->filterCartItemsWithProduct(
            $user->cartItems()->with(['product.categories'])->get()
        );

        if ($cartItems->isEmpty()) {
            return back()->withErrors(['promo_code' => 'Корзина пуста']);
        }

        try {
            $promo = $this->promoCodes->calculate($cartItems, $code, (int) $user->id);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->with('promo', null);
        }

        return back()->with('promo', $promo);
    }

    public function clearPromo()
    {
        return back()->with('promo', null);
    }

    public function addToCart(Request $request)
    {
        if ($response = $this->blockAdmin()) {
            return $response;
        }

        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1|max:999',
        ]);

        $user = Auth::user();
        $product = Product::findOrFail($validated['product_id']);

        if ($product->quantity <= 0) {
            return back()->withErrors(['cart' => 'Товар закончился, ожидайте пополнения']);
        }

        $cartItem = $user->cartItems()->where('product_id', $validated['product_id'])->first();

        $currentQty = $cartItem ? $cartItem->quantity : 0;
        if ($currentQty + $validated['quantity'] > $product->quantity) {
            return back()->withErrors(['cart' => $this->stockLimitMessage($product)]);
        }

        if ($cartItem) {
            $cartItem->increment('quantity', $validated['quantity']);
        } else {
            $user->cartItems()->create([
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
            ]);
        }

        return back()->with('success', 'Товар успешно добавлен в корзину');
    }

    public function updateQuantity(Request $request, $id)
    {
        if ($response = $this->blockAdmin()) {
            return $response;
        }

        $cartItem = Auth::user()->cartItems()->with('product.stockStatusRef')->findOrFail($id);
        $product = $cartItem->product;

        if (! $product) {
            $cartItem->delete();

            return back()->withErrors(['cart' => 'Товар больше недоступен и удалён из корзины']);
        }

        $maxQty = max(1, (int) $product->quantity);

        $validated = $request->validate([
            'quantity' => "required|integer|min:1|max:{$maxQty}",
        ]);

        if ($product->quantity <= 0) {
            return back()->withErrors(['cart' => 'Товар закончился, ожидайте пополнения']);
        }

        if ($validated['quantity'] > $product->quantity) {
            return back()->withErrors(['cart' => $this->stockLimitMessage($product)]);
        }

        $cartItem->update(['quantity' => $validated['quantity']]);

        return back();
    }

    public function removeFromCart($id)
    {
        if ($response = $this->blockAdmin()) {
            return $response;
        }

        Auth::user()->cartItems()->findOrFail($id)->delete();

        return back();
    }

    public function checkout(Request $request)
    {
        if ($response = $this->blockAdmin()) {
            return $response;
        }

        $user = Auth::user();
        $this->pruneOrphanCartItems($user);
        $cartItems = $this->checkout->filterCartItemsWithProduct(
            $user->cartItems()
                ->with(['product.stockStatusRef', 'product.images', 'product.categories'])
                ->get()
        );

        if ($cartItems->isEmpty()) {
            return back()->withErrors(['cart' => 'Корзина пуста']);
        }

        $validated = $request->validate([
            'delivery_address' => 'nullable|string|max:2000',
            'payment_method' => 'required|in:cash,card_courier,yookassa',
            'promo_code' => 'nullable|string|max:64',
        ]);

        $address = trim((string) ($validated['delivery_address'] ?? ''));
        if ($address === '') {
            $address = trim((string) ($user->delivery_address ?? ''));
        }
        if ($address === '') {
            return back()->withErrors(['delivery_address' => 'Укажите адрес доставки']);
        }

        try {
            $promoCode = session('promo.code');
            if (! $promoCode && ! empty($validated['promo_code'])) {
                $promoCode = PromoCodeValidator::normalizeCode($validated['promo_code']);
            }

            $result = $this->checkout->checkout(
                $user,
                $cartItems,
                $address,
                $validated['payment_method'],
                $promoCode ?: null,
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'cart' => 'Не удалось создать платёж. Попробуйте позже или выберите оплату при получении.',
            ]);
        }

        if (! $user->isAdmin()) {
            $user->update(['delivery_address' => $address]);
        }

        session()->forget('promo');

        if ($result['type'] === 'redirect') {
            return Inertia::location($result['url']);
        }

        return back()->with('success', 'Заказ создан и отправлен в обработку');
    }

    private function blockAdmin()
    {
        $user = Auth::user();
        if ($user?->isAdmin()) {
            return back()->withErrors(['cart' => 'Не доступно администратору']);
        }

        return null;
    }

    private function stockLimitMessage(Product $product): string
    {
        $available = max(0, (int) $product->quantity);

        if ($available <= 0) {
            return 'Товар на складе закончился';
        }

        return "Товар на складе закончился. Доступно только {$available} шт.";
    }

    private function pruneOrphanCartItems($user): void
    {
        $user->cartItems()->whereDoesntHave('product')->delete();
    }

    /**
     * @param  array<string, mixed>|null  $promo
     * @return array<string, mixed>|null
     */
    private function sanitizeSessionPromo($user, ?array $promo): ?array
    {
        if (! $promo || ($promo['type'] ?? '') !== PromoCode::TYPE_ORDER_PERCENT) {
            return $promo;
        }

        $model = PromoCode::findActiveByCode((string) ($promo['code'] ?? ''));
        if ($model && $model->wasUsedByUser((int) $user->id)) {
            session()->forget('promo');

            return null;
        }

        return $promo;
    }
}
