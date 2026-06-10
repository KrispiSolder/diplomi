<?php

namespace App\Services;

use App\Models\PromoCode;
use Illuminate\Validation\ValidationException;

class PromoCodeValidator
{
    public static function normalizeCode(string $code): string
    {
        return mb_strtoupper(trim($code));
    }

    public static function validateCodeFormat(string $code, string $field = 'code'): void
    {
        $code = self::normalizeCode($code);

        if ($code === '') {
            throw ValidationException::withMessages([
                $field => 'Введите промокод',
            ]);
        }

        if (! preg_match('/^[A-Z0-9]+$/', $code)) {
            throw ValidationException::withMessages([
                $field => 'Промокод: только латинские заглавные буквы (A–Z) и цифры, без пробелов',
            ]);
        }

        if (preg_match_all('/\d/', $code) < 2) {
            throw ValidationException::withMessages([
                $field => 'В промокоде должно быть минимум 2 цифры',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function assertNoActiveTargetConflict(array $data, ?int $ignoreId = null): void
    {
        if (! ($data['active'] ?? true)) {
            return;
        }

        $query = PromoCode::query()
            ->where('active', true)
            ->whereHas('promoTypeRef', fn ($q) => $q->where('code', $data['type']));

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($data['type'] === PromoCode::TYPE_ORDER_PERCENT) {
            if ($query->count() >= PromoCode::MAX_ACTIVE_ORDER_PERCENT) {
                throw ValidationException::withMessages([
                    'type' => 'Одновременно может быть не более '.PromoCode::MAX_ACTIVE_ORDER_PERCENT.' активных промокодов со скидкой % от суммы заказа. Деактивируйте лишние.',
                ]);
            }

            return;
        }

        if (! empty($data['product_id'])) {
            if ((clone $query)->where('product_id', $data['product_id'])->exists()) {
                throw ValidationException::withMessages([
                    'product_id' => 'Для этого товара уже есть активная акция типа «N+1 бесплатно»',
                ]);
            }

            return;
        }

        if (! empty($data['category_id'])) {
            if ((clone $query)
                ->where('category_id', $data['category_id'])
                ->whereNull('product_id')
                ->exists()) {
                throw ValidationException::withMessages([
                    'category_id' => 'Для этой категории уже есть активная акция типа «N+1 бесплатно»',
                ]);
            }
        }
    }
}
