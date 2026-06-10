<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            $product->syncStockStatusFromQuantity();
        });
    }

    public function syncStockStatusFromQuantity(): void
    {
        $inStockId = StockStatus::idFor('in_stock');
        $outOfStockId = StockStatus::idFor('out_of_stock');

        if ((int) $this->quantity > 0 && $inStockId) {
            $this->stock_status_id = $inStockId;

            return;
        }

        if ($outOfStockId) {
            $this->stock_status_id = $outOfStockId;
        }
    }

    protected $fillable = [
        'name',
        'slug',
        'article',
        'price',
        'description',
        'quantity',
        'stock_status_id',
        'care_difficulty_id',
        'product_size_id',
        'age_group_id',
    ];

    protected $appends = [
        'stock_status',
        'care_difficulty',
        'size',
        'age_group',
        'main_image',
        'category_id',
    ];

    public function stockStatusRef(): BelongsTo
    {
        return $this->belongsTo(StockStatus::class, 'stock_status_id');
    }

    public function careDifficultyRef(): BelongsTo
    {
        return $this->belongsTo(CareDifficulty::class, 'care_difficulty_id');
    }

    public function productSizeRef(): BelongsTo
    {
        return $this->belongsTo(ProductSize::class, 'product_size_id');
    }

    public function ageGroupRef(): BelongsTo
    {
        return $this->belongsTo(AgeGroup::class, 'age_group_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product')
            ->withPivot('is_primary');
    }

    public function resolvePrimaryCategory(): ?Category
    {
        $this->loadMissing('categories');

        $primary = $this->categories->first(fn (Category $c) => (bool) $c->pivot->is_primary);

        return $primary ?? $this->categories->first();
    }

    public function inCategorySlug(string $slug): bool
    {
        $this->loadMissing('categories');

        return $this->categories->contains(fn (Category $c) => $c->slug === $slug);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function hasActiveOrders(): bool
    {
        return $this->orderItems()
            ->whereHas('order', fn (Builder $query) => $query->excludingCancelled())
            ->exists();
    }

    public function averageRating(): float
    {
        return (float) ($this->reviews()->where('approved', true)->avg('rating') ?? 0);
    }

    public function reviewCount(): int
    {
        return $this->reviews()->where('approved', true)->count();
    }

    protected function stockStatus(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->stockStatusRef?->label,
            set: fn (?string $value) => [
                'stock_status_id' => StockStatus::idFor($value, 'in_stock'),
            ],
        );
    }

    protected function careDifficulty(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->careDifficultyRef?->code,
            set: fn (?string $value) => [
                'care_difficulty_id' => $value ? CareDifficulty::idFor($value) : null,
            ],
        );
    }

    protected function size(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->productSizeRef?->code,
            set: fn (?string $value) => [
                'product_size_id' => $value ? ProductSize::idFor($value) : null,
            ],
        );
    }

    protected function ageGroup(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->ageGroupRef?->code,
            set: fn (?string $value) => [
                'age_group_id' => $value ? AgeGroup::idFor($value) : null,
            ],
        );
    }

    protected function mainImage(): Attribute
    {
        return Attribute::make(
            get: function () {
                $this->loadMissing('images');

                return $this->images->first()?->image_url;
            },
            set: function (?string $url) {
                if ($url !== null && $url !== '' && $this->exists) {
                    $this->images()->updateOrCreate(
                        ['image_url' => $url],
                        ['order' => 0]
                    );
                }

                return [];
            },
        );
    }

    protected function categoryId(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->resolvePrimaryCategory()?->id,
        );
    }

    public static function syncCategoryPivot(self $product, array $categoryIds): void
    {
        $sync = [];
        foreach (array_values($categoryIds) as $index => $categoryId) {
            $sync[$categoryId] = ['is_primary' => $index === 0];
        }
        $product->categories()->sync($sync);
    }
}
