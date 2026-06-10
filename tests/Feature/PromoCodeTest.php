<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\StockStatus;
use App\Models\User;
use App\Services\PromoCodeService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PromoCodeTest extends TestCase
{
    use DatabaseTransactions;

    public function test_grunt4100_gives_free_fourth_soil_unit(): void
    {
        $this->assertNotNull(PromoCode::findActiveByCode('Grunt4100'));

        $user = User::factory()->create(['role' => 'user']);
        $soils = Category::query()->where('slug', 'soils')->first();
        $this->assertNotNull($soils);

        $product = Product::factory()->create([
            'price' => 400,
            'quantity' => 20,
            'stock_status_id' => StockStatus::idFor('in_stock'),
        ]);
        $product->categories()->sync([$soils->id => ['is_primary' => true]]);

        $user->cartItems()->create(['product_id' => $product->id, 'quantity' => 4]);
        $cartItems = $user->cartItems()->with('product.categories')->get();

        $result = app(PromoCodeService::class)->calculate($cartItems, 'Grunt4100');

        $this->assertSame(1600.0, $result['subtotal']);
        $this->assertSame(400.0, $result['discount']);
        $this->assertSame(1200.0, $result['total']);
        $this->assertSame(1, $result['free_units']);
    }

    public function test_order_percent_discount_from_minimum_total(): void
    {
        PromoCode::query()->updateOrCreate(
            ['code' => 'BIG10'],
            [
                'type' => PromoCode::TYPE_ORDER_PERCENT,
                'category_id' => null,
                'product_id' => null,
                'buy_quantity' => 3,
                'free_quantity' => 1,
                'min_order_amount' => 8000,
                'discount_percent' => 10,
                'description' => 'Скидка 10% от 8000 ₽',
                'active' => true,
            ]
        );

        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create([
            'price' => 4000,
            'quantity' => 10,
            'stock_status_id' => StockStatus::idFor('in_stock'),
        ]);

        $user->cartItems()->create(['product_id' => $product->id, 'quantity' => 2]);
        $cartItems = $user->cartItems()->with('product.categories')->get();

        $result = app(PromoCodeService::class)->calculate($cartItems, 'BIG10');

        $this->assertSame(8000.0, $result['subtotal']);
        $this->assertSame(800.0, $result['discount']);
        $this->assertSame(7200.0, $result['total']);
    }

    public function test_order_percent_cannot_be_reused_by_same_user(): void
    {
        PromoCode::query()->updateOrCreate(
            ['code' => 'ONCE15'],
            [
                'type' => PromoCode::TYPE_ORDER_PERCENT,
                'min_order_amount' => 1000,
                'discount_percent' => 15,
                'active' => true,
            ]
        );

        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create([
            'price' => 2000,
            'quantity' => 10,
            'stock_status_id' => StockStatus::idFor('in_stock'),
        ]);

        Order::query()->create([
            'user_id' => $user->id,
            'order_status_id' => OrderStatus::idFor('completed'),
            'delivery_address' => 'Тест',
            'promo_code' => 'ONCE15',
            'discount_amount' => 300,
        ]);

        $user->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);
        $cartItems = $user->cartItems()->with('product.categories')->get();

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(PromoCodeService::class)->calculate($cartItems, 'ONCE15', $user->id);
    }

    public function test_cart_lists_up_to_three_order_percent_offers(): void
    {
        PromoCode::query()->whereHas('promoTypeRef', fn ($q) => $q->where('code', PromoCode::TYPE_ORDER_PERCENT))
            ->update(['active' => false]);

        foreach ([['LOW05', 3000, 5], ['MID10', 5000, 10], ['TOP15', 8000, 15]] as [$code, $min, $pct]) {
            PromoCode::query()->create([
                'code' => $code,
                'type' => PromoCode::TYPE_ORDER_PERCENT,
                'min_order_amount' => $min,
                'discount_percent' => $pct,
                'active' => true,
            ]);
        }

        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create([
            'price' => 6000,
            'quantity' => 5,
            'stock_status_id' => StockStatus::idFor('in_stock'),
        ]);
        $user->cartItems()->create(['product_id' => $product->id, 'quantity' => 1]);

        $response = $this->actingAs($user)->get('/cart');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('orderPercentOffers', 3)
            ->where('orderPercentOffers.0.code', 'LOW05')
            ->where('orderPercentOffers.0.eligible', true)
            ->where('orderPercentOffers.2.code', 'TOP15')
        );
    }
}
