<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromoCode extends Model
{
    public const TYPE_BUNDLE_FREE = 'bundle_free';

    public const TYPE_ORDER_PERCENT = 'order_percent';

    public const MAX_ACTIVE_ORDER_PERCENT = 3;

    protected $fillable = [
        'code',
        'type',
        'promo_type_id',
        'category_id',
        'product_id',
        'buy_quantity',
        'free_quantity',
        'min_order_amount',
        'discount_percent',
        'description',
        'active',
    ];

    protected $appends = ['condition_summary', 'type'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'buy_quantity' => 'integer',
            'free_quantity' => 'integer',
            'min_order_amount' => 'float',
            'discount_percent' => 'float',
        ];
    }

    public function promoTypeRef(): BelongsTo
    {
        return $this->belongsTo(PromoType::class, 'promo_type_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function type(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->promoTypeRef?->code
                ?? PromoType::query()->find($this->promo_type_id)?->code,
            set: function (?string $value) {
                if ($value === null || $value === '') {
                    return [];
                }

                $id = PromoType::idFor($value);

                return $id ? ['promo_type_id' => $id] : [];
            },
        );
    }

    public function isBundleFree(): bool
    {
        return $this->type === self::TYPE_BUNDLE_FREE;
    }

    public function isOrderPercent(): bool
    {
        return $this->type === self::TYPE_ORDER_PERCENT;
    }

    public function setSize(): int
    {
        return (int) $this->buy_quantity + (int) $this->free_quantity;
    }

    public function wasUsedByUser(int $userId): bool
    {
        $normalized = mb_strtoupper(trim($this->code));
        $cancelledId = Order::cancelledStatusId();

        return Order::query()
            ->where('user_id', $userId)
            ->whereNotNull('promo_code')
            ->whereRaw('UPPER(promo_code) = ?', [$normalized])
            ->when($cancelledId, fn ($q) => $q->where(function ($query) use ($cancelledId) {
                $query->whereNull('order_status_id')
                    ->orWhere('order_status_id', '!=', $cancelledId);
            }))
            ->exists();
    }

    public static function findActiveByCode(string $code): ?self
    {
        $normalized = mb_strtoupper(trim($code));

        return static::query()
            ->with('promoTypeRef')
            ->where('active', true)
            ->whereRaw('UPPER(code) = ?', [$normalized])
            ->first();
    }

    public function appliesToProduct(Product $product): bool
    {
        if ($this->isOrderPercent()) {
            return true;
        }

        if ($this->product_id) {
            return (int) $product->id === (int) $this->product_id;
        }

        if ($this->category_id) {
            $product->loadMissing('categories');

            return $product->categories->contains('id', (int) $this->category_id);
        }

        return false;
    }

    public static function bannerForCatalogCategory(string $categorySlug): ?array
    {
        if ($categorySlug === 'all') {
            return null;
        }

        $categoryId = Category::query()->where('slug', $categorySlug)->value('id');
        if (! $categoryId) {
            return null;
        }

        $promo = static::query()
            ->with('promoTypeRef')
            ->where('active', true)
            ->whereHas('promoTypeRef', fn ($q) => $q->where('code', self::TYPE_BUNDLE_FREE))
            ->where('category_id', $categoryId)
            ->whereNull('product_id')
            ->first();

        return $promo?->toPublicArray();
    }

    public static function bannerForProduct(Product $product): ?array
    {
        $product->loadMissing('categories');

        $promos = static::query()->where('active', true)->with(['product', 'promoTypeRef', 'category'])->get();

        foreach ($promos as $promo) {
            if ($promo->isOrderPercent()) {
                continue;
            }
            if ($promo->appliesToProduct($product)) {
                return $promo->toPublicArray();
            }
        }

        return null;
    }

    /**
     * @return array{code: string, description: string|null, hint: string, type: string}
     */
    public function toPublicArray(): array
    {
        return [
            'code' => $this->code,
            'description' => $this->description,
            'hint' => $this->publicHint(),
            'type' => $this->type,
        ];
    }

    public function publicHint(): string
    {
        if ($this->isOrderPercent()) {
            $min = number_format((float) $this->min_order_amount, 0, '.', ' ');

            return "При заказе от {$min} ₽ — скидка {$this->discount_percent}% на всю корзину. Введите промокод при оформлении.";
        }

        $setSize = $this->setSize();
        $target = $this->product_id
            ? 'на товар «'.($this->product?->name ?? 'выбранный').'»'
            : 'на грунты в корзине';

        return "При покупке {$setSize} ед. {$target} — {$this->free_quantity}-я бесплатно (100%). Введите промокод при оформлении.";
    }

    protected function conditionSummary(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->isOrderPercent()) {
                    $min = number_format((float) $this->min_order_amount, 0, '.', ' ');

                    return "Заказ от {$min} ₽ → −{$this->discount_percent}%";
                }

                $target = $this->product_id
                    ? ($this->product?->name ?? "товар #{$this->product_id}")
                    : ($this->category?->name ?? ($this->category_id ? "категория #{$this->category_id}" : '—'));

                return "Каждые {$this->setSize()} шт. ({$this->buy_quantity}+{$this->free_quantity}): {$target}";
            },
        );
    }
}
