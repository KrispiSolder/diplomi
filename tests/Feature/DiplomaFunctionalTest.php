<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Запуск :
 *   php artisan test --configuration=phpunit.diploma.xml
 */
class DiplomaFunctionalTest extends TestCase
{
    use DatabaseTransactions;

    private function createInStockProduct(int $quantity = 10, float $price = 1500.0): Product
    {
        return Product::factory()->create([
            'quantity' => $quantity,
            'price' => $price,
            'stock_status_id' => StockStatus::idFor('in_stock'),
        ]);
    }

    private function createRegularUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'user',
            'delivery_address' => 'г. Иркутск, ул. Ленина, 7Б',
        ], $overrides));
    }

    /** Сценарий 1: регистрация с валидными данными — пользователь в БД */
    public function test_scenario_1_user_registration_with_valid_data_creates_user_in_database(): void
    {
        $email = 'diploma_register_'.uniqid().'@example.com';

        $response = $this->post('/register', [
            'name' => 'Диплом Тест',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => $email,
            'name' => 'Диплом Тест',
        ]);
    }

    /** Сценарий 2: добавление товара в корзину авторизованным пользователем */
    public function test_scenario_2_authenticated_user_can_add_product_to_cart(): void
    {
        $user = $this->createRegularUser();
        $product = $this->createInStockProduct();

        $response = $this->actingAs($user)->post('/cart/add', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    /** Сценарий 3: оформление заказа — запись в orders, корзина пуста */
    public function test_scenario_3_authenticated_user_can_checkout_order_and_clear_cart(): void
    {
        $user = $this->createRegularUser();
        $product = $this->createInStockProduct(10, 2000.0);

        $this->actingAs($user)->post('/cart/add', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($user)->post('/cart/checkout', [
            'delivery_address' => 'г. Иркутск, ул. Тестовая, 1',
            'payment_method' => 'cash',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'delivery_address' => 'г. Иркутск, ул. Тестовая, 1',
        ]);
        $order = Order::where('user_id', $user->id)
            ->where('delivery_address', 'г. Иркутск, ул. Тестовая, 1')
            ->first();
        $this->assertNotNull($order);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
        $this->assertEquals(0, CartItem::where('user_id', $user->id)->count());
    }

    /** Сценарий 4 (негативный): регистрация с некорректным email */
    public function test_scenario_4_registration_with_invalid_email_returns_validation_error(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'Неверный Email',
            'email' => 'not-valid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertEquals(0, User::where('name', 'Неверный Email')->count());
    }

    /** Сценарий 5 (негативный): обычный пользователь не может открыть /admin/dashboard */
    public function test_scenario_5_regular_user_cannot_access_admin_dashboard(): void
    {
        $user = $this->createRegularUser(['role' => 'user']);

        $response = $this->actingAs($user)->get('/admin/dashboard');

        $response->assertStatus(403);
    }

    /** Сценарий 6 (негативный): гость не может удалить товар через admin API */
    public function test_scenario_6_guest_cannot_delete_product_via_admin_route(): void
    {
        $product = $this->createInStockProduct();

        $response = $this->delete('/admin/products/'.$product->id);

        $this->assertGuest();
        $response->assertRedirect('/login');
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }
}
