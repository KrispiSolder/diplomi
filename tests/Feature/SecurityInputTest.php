<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Проверка, что подмена данных (в т.ч. через DevTools) отклоняется на сервере.
 */
class SecurityInputTest extends TestCase
{
    use DatabaseTransactions;

    private function regularUser(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    private function inStockProduct(int $qty = 5): Product
    {
        return Product::factory()->create([
            'quantity' => $qty,
            'price' => 1000,
            'stock_status_id' => StockStatus::idFor('in_stock'),
        ]);
    }

    public function test_cart_rejects_negative_quantity_on_add(): void
    {
        $user = $this->regularUser();
        $product = $this->inStockProduct();

        $response = $this->actingAs($user)->from('/cart')->post('/cart/add', [
            'product_id' => $product->id,
            'quantity' => -3,
        ]);

        $response->assertSessionHasErrors('quantity');
        $this->assertDatabaseMissing('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_cart_rejects_zero_quantity_on_add(): void
    {
        $user = $this->regularUser();
        $product = $this->inStockProduct();

        $response = $this->actingAs($user)->post('/cart/add', [
            'product_id' => $product->id,
            'quantity' => 0,
        ]);

        $response->assertSessionHasErrors('quantity');
    }

    public function test_cart_rejects_quantity_above_stock_on_update(): void
    {
        $user = $this->regularUser();
        $product = $this->inStockProduct(2);

        $this->actingAs($user)->post('/cart/add', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $cartItem = $user->cartItems()->first();

        $response = $this->actingAs($user)->patch("/cart/{$cartItem->id}/quantity", [
            'quantity' => 99,
        ]);

        $response->assertSessionHasErrors('quantity');
        $this->assertEquals(1, $cartItem->fresh()->quantity);
    }

    public function test_checkout_rejects_invalid_payment_method(): void
    {
        $user = $this->regularUser();
        $product = $this->inStockProduct();

        $this->actingAs($user)->post('/cart/add', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($user)->post('/cart/checkout', [
            'delivery_address' => 'г. Иркутск, ул. Тестовая, 1',
            'payment_method' => 'free_hack',
        ]);

        $response->assertSessionHasErrors('payment_method');
        $this->assertEquals(1, $user->cartItems()->count());
    }

    public function test_checkout_rejects_yookassa_when_not_configured(): void
    {
        config([
            'services.yookassa.shop_id' => '',
            'services.yookassa.secret_key' => '',
            'services.yookassa.enabled' => true,
        ]);

        $user = $this->regularUser();
        $product = $this->inStockProduct();

        $this->actingAs($user)->post('/cart/add', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($user)->post('/cart/checkout', [
            'delivery_address' => 'г. Иркутск, ул. Тестовая, 1',
            'payment_method' => 'yookassa',
        ]);

        $response->assertSessionHasErrors('payment_method');
        $this->assertEquals(1, $user->cartItems()->count());
    }

    public function test_checkout_uses_server_price_not_client_input(): void
    {
        $user = $this->regularUser();
        $product = $this->inStockProduct(5);
        $product->update(['price' => 2500]);

        $this->actingAs($user)->post('/cart/add', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->actingAs($user)->post('/cart/checkout', [
            'delivery_address' => 'г. Иркутск, ул. Тестовая, 1',
            'payment_method' => 'cash',
            'total' => 1,
            'price' => 1,
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'price' => 2500,
        ]);
    }
}
