<?php

namespace App\Services;

use App\Models\PromoCode;
use App\Models\Product;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PromoCodeService
{
    /**
     * @param  Collection<int, \App\Models\CartItem>  $cartItems
     * @return array{
     *     code: string,
     *     description: string|null,
     *     discount: float,
     *     subtotal: float,
     *     total: float,
     *     type: string,
     *     free_units?: int,
     *     soil_quantity?: int
     * }
     */
    public function calculate(Collection $cartItems, string $promoCode, ?int $userId = null): array
    {
        PromoCodeValidator::validateCodeFormat($promoCode, 'promo_code');

        $promo = PromoCode::findActiveByCode($promoCode);
        if (! $promo) {
            throw ValidationException::withMessages([
                'promo_code' => 'Промокод не найден или недействителен',
            ]);
        }

        $cartItems->loadMissing('product.categories');
        $subtotal = $this->cartSubtotal($cartItems);

        if ($promo->isOrderPercent()) {
            $this->assertOrderPercentUsableByUser($promo, $userId);

            return $this->calculateOrderPercent($promo, $subtotal);
        }

        return $this->calculateBundleFree($promo, $cartItems, $subtotal);
    }

    /**
     * @param  Collection<int, \App\Models\CartItem>  $cartItems
     * @return list<array{
     *     code: string,
     *     description: string|null,
     *     discount_percent: float,
     *     min_order_amount: float,
     *     eligible: bool,
     *     shortfall: float,
     *     potential_discount: float|null
     * }>
     */
    public function orderPercentOffersForCart(User $user, Collection $cartItems): array
    {
        $subtotal = $this->cartSubtotal($cartItems);

        return PromoCode::query()
            ->where('active', true)
            ->whereHas('promoTypeRef', fn ($q) => $q->where('code', PromoCode::TYPE_ORDER_PERCENT))
            ->orderBy('min_order_amount')
            ->orderBy('code')
            ->limit(PromoCode::MAX_ACTIVE_ORDER_PERCENT)
            ->get()
            ->filter(fn (PromoCode $promo) => ! $promo->wasUsedByUser((int) $user->id))
            ->take(PromoCode::MAX_ACTIVE_ORDER_PERCENT)
            ->map(function (PromoCode $promo) use ($subtotal) {
                $min = Money::roundRubles((float) $promo->min_order_amount);
                $subtotalK = Money::toKopecks($subtotal);
                $minK = Money::toKopecks($min);
                $eligible = $subtotalK >= $minK;
                $discountK = $eligible
                    ? Money::percentOf($subtotalK, (float) $promo->discount_percent)
                    : 0;

                return [
                    'code' => $promo->code,
                    'description' => $promo->description,
                    'discount_percent' => Money::roundRubles((float) $promo->discount_percent),
                    'min_order_amount' => $min,
                    'eligible' => $eligible,
                    'shortfall' => Money::fromKopecks(max(0, $minK - $subtotalK)),
                    'potential_discount' => $eligible ? Money::fromKopecks($discountK) : null,
                ];
            })
            ->values()
            ->all();
    }

    private function assertOrderPercentUsableByUser(PromoCode $promo, ?int $userId): void
    {
        if (! $userId) {
            return;
        }

        if ($promo->wasUsedByUser($userId)) {
            throw ValidationException::withMessages([
                'promo_code' => "Промокод «{$promo->code}» уже был использован в вашем предыдущем заказе и действует один раз.",
            ]);
        }
    }

    /**
     * @param  Collection<int, \App\Models\CartItem>  $cartItems
     */
    public function cartSubtotal(Collection $cartItems): float
    {
        $kopecks = 0;

        foreach ($cartItems as $item) {
            if ($item->product === null) {
                continue;
            }

            $kopecks += Money::lineTotalKopecks($item->product->price, (int) $item->quantity);
        }

        return Money::fromKopecks($kopecks);
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateOrderPercent(PromoCode $promo, float $subtotal): array
    {
        $min = Money::roundRubles((float) $promo->min_order_amount);
        $subtotalK = Money::toKopecks($subtotal);
        $minK = Money::toKopecks($min);

        if ($subtotalK < $minK) {
            $minFormatted = number_format($min, 2, '.', ' ');
            $shortfall = number_format(Money::fromKopecks($minK - $subtotalK), 2, '.', ' ');
            $current = number_format(Money::fromKopecks($subtotalK), 2, '.', ' ');

            throw ValidationException::withMessages([
                'promo_code' => "Промокод «{$promo->code}»: условие не выполнено. Нужна сумма заказа от {$minFormatted} ₽ (скидка {$promo->discount_percent}%), сейчас {$current} ₽ — не хватает {$shortfall} ₽.",
            ]);
        }

        $discountK = Money::percentOf($subtotalK, (float) $promo->discount_percent);
        $totalK = $subtotalK - $discountK;

        return [
            'code' => $promo->code,
            'description' => $promo->description,
            'type' => $promo->type,
            'discount' => Money::fromKopecks($discountK),
            'subtotal' => Money::fromKopecks($subtotalK),
            'total' => Money::fromKopecks($totalK),
        ];
    }

    /**
     * @param  Collection<int, \App\Models\CartItem>  $cartItems
     * @return array<string, mixed>
     */
    private function calculateBundleFree(PromoCode $promo, Collection $cartItems, float $subtotal): array
    {
        $promo->loadMissing('product');
        $units = $this->collectBundleUnits($promo, $cartItems);
        $qty = count($units);
        $setSize = $promo->setSize();
        $targetLabel = $this->bundleTargetLabel($promo);

        if ($qty === 0) {
            throw ValidationException::withMessages([
                'promo_code' => "Промокод «{$promo->code}» не подходит к товарам в корзине. Добавьте {$targetLabel}.",
            ]);
        }

        if ($qty < $setSize) {
            $need = $setSize - $qty;

            throw ValidationException::withMessages([
                'promo_code' => "Промокод «{$promo->code}»: условие не выполнено. Нужно {$setSize} шт. {$targetLabel} (купите {$promo->buy_quantity}, {$promo->free_quantity}-я бесплатно). В корзине сейчас {$qty} — добавьте ещё {$need} шт.",
            ]);
        }

        $freeUnits = intdiv($qty, $setSize);
        usort($units, fn (array $a, array $b) => $a['price'] <=> $b['price']);

        $subtotalK = Money::toKopecks($subtotal);
        $discountK = 0;
        for ($i = 0; $i < $freeUnits; $i++) {
            $discountK += Money::toKopecks($units[$i]['price']);
        }

        return [
            'code' => $promo->code,
            'description' => $promo->description,
            'type' => $promo->type,
            'discount' => Money::fromKopecks($discountK),
            'soil_quantity' => $qty,
            'free_units' => $freeUnits,
            'subtotal' => Money::fromKopecks($subtotalK),
            'total' => Money::fromKopecks($subtotalK - $discountK),
        ];
    }

    /**
     * @param  Collection<int, \App\Models\CartItem>  $cartItems
     * @return list<array{price: float}>
     */
    private function collectBundleUnits(PromoCode $promo, Collection $cartItems): array
    {
        $units = [];

        foreach ($cartItems as $item) {
            $product = $item->product;
            if (! $product instanceof Product || ! $promo->appliesToProduct($product)) {
                continue;
            }

            $price = (float) $product->price;
            for ($i = 0; $i < (int) $item->quantity; $i++) {
                $units[] = ['price' => $price];
            }
        }

        return $units;
    }

    private function bundleTargetLabel(PromoCode $promo): string
    {
        if ($promo->product_id && $promo->product) {
            return 'товара «'.$promo->product->name.'»';
        }

        if ($promo->category_id) {
            $promo->loadMissing('category');

            return 'из категории «'.($promo->category?->name ?? 'выбранной').'»';
        }

        return 'по акции';
    }
}
