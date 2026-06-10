<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\OrderStatus;
use App\Models\PaymentMethod;
use App\Models\PaymentStatus;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private ProductStockService $stock,
        private YooKassaService $yookassa,
        private PromoCodeService $promoCodes,
    ) {}

    /**
     * @param  Collection<int, \App\Models\CartItem>  $cartItems
     * @return array{type: 'completed'}|array{type: 'redirect', url: string}
     */
    public function checkout(
        User $user,
        Collection $cartItems,
        string $address,
        string $paymentMethodCode,
        ?string $promoCode = null,
    ): array {
        $promo = $this->resolvePromo($user, $cartItems, $promoCode);

        $this->assertNoPendingYooKassaOrder($user);

        if ($paymentMethodCode === 'yookassa') {
            return $this->checkoutWithYooKassa($user, $cartItems, $address, $promo);
        }

        $this->checkoutOffline($user, $cartItems, $address, $paymentMethodCode, $promo);

        return ['type' => 'completed'];
    }

    /**
     * @return array{code: string, discount: float}|null
     */
    private function resolvePromo(User $user, Collection $cartItems, ?string $promoCode): ?array
    {
        $promoCode = trim((string) $promoCode);
        if ($promoCode === '') {
            return null;
        }

        $result = $this->promoCodes->calculate($cartItems, $promoCode, (int) $user->id);

        return [
            'code' => $result['code'],
            'discount' => $result['discount'],
        ];
    }

    /**
     * @param  Collection<int, \App\Models\CartItem>  $cartItems
     */
    private function checkoutOffline(
        User $user,
        Collection $cartItems,
        string $address,
        string $paymentMethodCode,
        ?array $promo = null,
    ): void {
        $paymentMethodId = PaymentMethod::idFor($paymentMethodCode);

        if (! $paymentMethodId) {
            throw ValidationException::withMessages([
                'payment_method' => 'Выберите способ оплаты',
            ]);
        }

        $unpaidStatusId = Order::paymentStatusId('unpaid')
            ?? Order::paymentStatusId('pending');

        DB::transaction(function () use ($user, $cartItems, $address, $paymentMethodId, $unpaidStatusId, $promo) {
            $this->assertCartStockAvailable($cartItems);

            $order = $user->orders()->create([
                'delivery_address' => $address,
                'order_status_id' => OrderStatus::idForLabel('в обработке'),
                'payment_method_id' => $paymentMethodId,
                'payment_status_id' => $unpaidStatusId,
                'promo_code' => $promo['code'] ?? null,
                'discount_amount' => $promo['discount'] ?? 0,
            ]);

            foreach ($cartItems as $item) {
                $product = Product::query()->lockForUpdate()->findOrFail($item->product_id);

                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_name' => $product->name,
                    'quantity' => $item->quantity,
                    'price' => $product->price,
                ]);

                $this->stock->deductForOrder($product, (int) $item->quantity);
            }

            $user->cartItems()->delete();
        });
    }

    /**
     * @param  Collection<int, \App\Models\CartItem>  $cartItems
     * @return array{type: 'redirect', url: string}
     */
    private function checkoutWithYooKassa(
        User $user,
        Collection $cartItems,
        string $address,
        ?array $promo = null,
    ): array {
        if (! $this->yookassa->isConfigured()) {
            throw ValidationException::withMessages([
                'payment_method' => 'Онлайн-оплата временно недоступна. Выберите оплату при получении.',
            ]);
        }

        $paymentMethodId = PaymentMethod::idFor('yookassa');
        if (! $paymentMethodId) {
            throw ValidationException::withMessages([
                'payment_method' => 'Способ оплаты не настроен',
            ]);
        }

        $order = DB::transaction(function () use ($user, $cartItems, $address, $paymentMethodId, $promo) {
            $this->assertCartStockAvailable($cartItems);

            $order = $user->orders()->create([
                'delivery_address' => $address,
                'order_status_id' => Order::awaitingPaymentStatusId(),
                'payment_method_id' => $paymentMethodId,
                'payment_status_id' => Order::paymentStatusId('pending'),
                'promo_code' => $promo['code'] ?? null,
                'discount_amount' => $promo['discount'] ?? 0,
            ]);

            foreach ($cartItems as $item) {
                $product = Product::query()->lockForUpdate()->findOrFail($item->product_id);

                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_name' => $product->name,
                    'quantity' => $item->quantity,
                    'price' => $product->price,
                ]);

                $this->stock->deductForOrder($product, (int) $item->quantity);
            }

            return $order;
        });

        $confirmationUrl = $this->yookassa->createPaymentForOrder($order);

        return ['type' => 'redirect', 'url' => $confirmationUrl];
    }

    public function completeYooKassaPayment(Order $order, ?string $paymentId = null): void
    {
        DB::transaction(function () use ($order, $paymentId) {
            $order = Order::query()->lockForUpdate()->with('items', 'user')->find($order->id);
            if (! $order || $order->isPaid() || $order->isCancelled()) {
                return;
            }

            if ($paymentId) {
                $order->yookassa_payment_id = $paymentId;
            }

            $order->payment_status_id = Order::paymentStatusId('paid');
            $order->order_status_id = OrderStatus::idForLabel('в обработке');
            $order->save();

            $order->user?->cartItems()->delete();
        });
    }

    public function failYooKassaPayment(Order $order, string $paymentStatusCode = 'canceled'): void
    {
        DB::transaction(function () use ($order, $paymentStatusCode) {
            $order = Order::query()->lockForUpdate()->with('items.product')->find($order->id);
            if (! $order || $order->isPaid() || $order->isCancelled()) {
                return;
            }

            if ($order->isAwaitingPayment()) {
                foreach ($order->items as $item) {
                    $product = $item->product;
                    if ($product instanceof Product) {
                        $this->stock->restoreAfterOrderCancel($product, (int) $item->quantity);
                    }
                }
            }

            $statusId = Order::paymentStatusId($paymentStatusCode)
                ?? Order::paymentStatusId('failed');

            $order->payment_status_id = $statusId;
            $order->status = 'отменен';
            $order->save();
        });
    }

    public function syncOrderPayment(Order $order): void
    {
        if (! $order->usesYooKassa() || $order->isPaid() || $order->isCancelled()) {
            return;
        }

        $paymentId = $order->yookassa_payment_id;
        if (! $paymentId) {
            return;
        }

        $payment = $this->yookassa->fetchPayment($paymentId);
        if ($payment) {
            $this->yookassa->syncOrderFromPayment($order, $payment);
        }
    }

    /**
     * @param  Collection<int, \App\Models\CartItem>  $cartItems
     */
    public function assertCartStockAvailable(Collection $cartItems): void
    {
        $cartItems = $this->filterCartItemsWithProduct($cartItems);

        if ($cartItems->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'В корзине нет доступных товаров для заказа',
            ]);
        }

        foreach ($cartItems as $item) {
            $product = Product::query()->lockForUpdate()->find($item->product_id);
            if (! $product || $product->quantity <= 0) {
                throw ValidationException::withMessages([
                    'cart' => 'Товар «'.($product?->name ?? 'неизвестен').'» закончился и недоступен для заказа',
                ]);
            }
            if ($item->quantity < 1 || $item->quantity > $product->quantity) {
                $available = max(0, (int) $product->quantity);
                $message = $available <= 0
                    ? 'Товар на складе закончился'
                    : "Товар на складе закончился. Доступно только {$available} шт.";

                throw ValidationException::withMessages([
                    'cart' => 'Товара «'.$product->name.'» больше нет в наличии. '.$message,
                ]);
            }
        }
    }

    public function assertNoPendingYooKassaOrder(User $user): void
    {
        $awaitingId = Order::awaitingPaymentStatusId();
        if (! $awaitingId) {
            return;
        }

        $hasPending = $user->orders()
            ->where('order_status_id', $awaitingId)
            ->whereHas('paymentMethodRef', fn ($q) => $q->where('code', 'yookassa'))
            ->exists();

        if ($hasPending) {
            throw ValidationException::withMessages([
                'cart' => 'У вас уже есть неоплаченный онлайн-заказ. Оплатите его в личном кабинете или отмените перед новым оформлением.',
            ]);
        }
    }

    /**
     * @param  Collection<int, \App\Models\CartItem>  $cartItems
     * @return Collection<int, \App\Models\CartItem>
     */
    public function filterCartItemsWithProduct(Collection $cartItems): Collection
    {
        return $cartItems->filter(fn ($item) => $item->relationLoaded('product')
            ? $item->product !== null
            : Product::query()->whereKey($item->product_id)->exists()
        )->values();
    }

}
