<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_status_id',
        'payment_method_id',
        'payment_status_id',
        'yookassa_payment_id',
        'delivery_address',
        'promo_code',
        'discount_amount',
    ];

    protected $appends = ['status', 'total_amount', 'payment_method', 'payment_method_code', 'payment_status', 'cancel_blocked'];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderStatusRef(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'order_status_id');
    }

    public function paymentMethodRef(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function paymentStatusRef(): BelongsTo
    {
        return $this->belongsTo(PaymentStatus::class, 'payment_status_id');
    }

    public static function awaitingPaymentStatusId(): ?int
    {
        static $id = null;

        $id ??= OrderStatus::idFor('awaiting_payment')
            ?? OrderStatus::idForLabel('ожидает оплаты');

        return $id;
    }

    public static function paymentStatusId(string $code): ?int
    {
        return PaymentStatus::idFor($code);
    }

    public function isAwaitingPayment(): bool
    {
        $awaitingId = static::awaitingPaymentStatusId();

        return $awaitingId && (int) $this->order_status_id === (int) $awaitingId;
    }

    public function isPaid(): bool
    {
        $paidId = static::paymentStatusId('paid');

        return $paidId && (int) $this->payment_status_id === (int) $paidId;
    }

    public function usesYooKassa(): bool
    {
        return $this->paymentMethodRef?->code === 'yookassa';
    }

    public function usesOfflinePayment(): bool
    {
        return in_array($this->payment_method_code, ['cash', 'card_courier'], true);
    }

    public function markOfflinePaymentReceived(): void
    {
        if (! $this->usesOfflinePayment()) {
            return;
        }

        $paidId = static::paymentStatusId('paid');
        if ($paidId) {
            $this->payment_status_id = $paidId;
        }
    }

    public function canBeCancelledByUser(): bool
    {
        if ($this->isCancelled()) {
            return false;
        }

        if (! in_array($this->status, ['в обработке', 'ожидает оплаты'], true)) {
            return false;
        }

        if ($this->usesYooKassa() && $this->status === 'в обработке') {
            return false;
        }

        return true;
    }

    /** @deprecated use orderStatusRef */
    public function orderStatus(): BelongsTo
    {
        return $this->orderStatusRef();
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public static function cancelledStatusId(): ?int
    {
        static $id = null;

        $id ??= OrderStatus::idForLabel('отменен');

        return $id;
    }

    public function scopeExcludingCancelled(Builder $query): Builder
    {
        $cancelledId = static::cancelledStatusId();
        if (! $cancelledId) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($cancelledId) {
            $q->whereNull('order_status_id')
                ->orWhere('order_status_id', '!=', $cancelledId);
        });
    }

    public function scopeCancelledOnly(Builder $query): Builder
    {
        $cancelledId = static::cancelledStatusId();
        if (! $cancelledId) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where('order_status_id', $cancelledId);
    }

    public function isCancelled(): bool
    {
        $cancelledId = static::cancelledStatusId();

        return $cancelledId && (int) $this->order_status_id === (int) $cancelledId;
    }

    public function isCompleted(): bool
    {
        return $this->status === 'выполнен';
    }

    public static function completedStatusId(): ?int
    {
        static $id = null;

        $id ??= OrderStatus::idForLabel('выполнен');

        return $id;
    }

    /** @return list<int> */
    public static function reviewableStatusIds(): array
    {
        $id = static::completedStatusId();

        return $id ? [$id] : [];
    }

    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->orderStatusRef?->label,
            set: function (?string $value) {
                if ($value === null || $value === '') {
                    return [];
                }

                return [
                    'order_status_id' => OrderStatus::idForLabel($value),
                ];
            },
        );
    }

    protected function paymentMethodCode(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->paymentMethodRef?->code,
        );
    }

    protected function cancelBlocked(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->usesYooKassa() && $this->status === 'в обработке',
        );
    }

    protected function paymentMethod(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->paymentMethodRef?->label,
            set: function (?string $value) {
                if ($value === null || $value === '') {
                    return ['payment_method_id' => null];
                }

                return [
                    'payment_method_id' => PaymentMethod::idFor($value),
                ];
            },
        );
    }

    protected function paymentStatus(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->paymentStatusRef?->label,
            set: function (?string $value) {
                if ($value === null || $value === '') {
                    return ['payment_status_id' => null];
                }

                return [
                    'payment_status_id' => PaymentStatus::idFor($value),
                ];
            },
        );
    }

    protected function totalAmount(): Attribute
    {
        return Attribute::make(
            get: function () {
                $items = $this->relationLoaded('items')
                    ? $this->items
                    : $this->items()->get();

                $itemsTotalK = 0;
                foreach ($items as $item) {
                    $itemsTotalK += \App\Support\Money::lineTotalKopecks($item->price, (int) $item->quantity);
                }

                $discountK = \App\Support\Money::toKopecks((float) ($this->discount_amount ?? 0));

                return \App\Support\Money::fromKopecks(max(0, $itemsTotalK - $discountK));
            },
        );
    }
}
