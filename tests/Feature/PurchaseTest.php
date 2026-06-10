<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use DatabaseTransactions;

    

    protected function createProduct(int $qty = 5, string $status = 'В наличии', float $price = 1000.0): Product
    {
        return Product::factory()
            ->withStockStatus($status)
            ->create([
                'quantity' => $qty,
                'price' => $price,
            ]);
    }

   
    public function test_user_can_successfully_purchase_product(): void
    {
        $user = User::factory()->create([
            'delivery_address' => 'ул. Тестовая, д. 1'
        ]);
        
        $product = $this->createProduct(10, 'В наличии', 1500.0);

        
        $this->actingAs($user)->post('/cart/add', [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);


        $response = $this->actingAs($user)->post('/cart/checkout', [
            'delivery_address' => 'ул. Тестовая, д. 1',
            'payment_method' => 'cash',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertEquals(1, Order::where('user_id', $user->id)->count());
        $order = Order::with('items')->where('user_id', $user->id)->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertSame(OrderStatus::idForLabel('в обработке'), $order->order_status_id);
        $this->assertEquals(4500.0, $order->total_amount);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'price' => 1500.0,
        ]);

        // Проверяем, что количество товара уменьшилось
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity' => 7,
        ]);

        $this->assertEquals(0, CartItem::where('user_id', $user->id)->count());
    }

    /** Тест 2: Невозможно купить товар, который закончился */
    public function test_user_cannot_purchase_out_of_stock_product(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(0, 'Закончился', 2000.0);

        // Пытаемся добавить товар, который закончился
        $response = $this->actingAs($user)->post('/cart/add', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertSessionHasErrors('cart');

        $this->assertEquals(0, CartItem::where('user_id', $user->id)->count());
    }

    /** Тест 3: Невозможно оформить заказ с пустой корзиной */
    public function test_user_cannot_checkout_with_empty_cart(): void
    {
        $user = User::factory()->create();

        // Пытаемся оформить заказ с пустой корзиной
        $response = $this->actingAs($user)->post('/cart/checkout');

        $response->assertSessionHasErrors('cart');

        $this->assertEquals(0, Order::where('user_id', $user->id)->count());
    }
}


