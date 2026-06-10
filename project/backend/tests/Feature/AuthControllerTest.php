<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Сценарий FUNC-POS-01: Регистрация нового пользователя с корректными данными
     */
    public function test_user_can_register()
    {
        $userData = [
            'name' => 'Тестовый Пользователь',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '+79001234567',
            'address_line' => 'ул. Тестовая, д. 1',
            'city' => 'Москва'
        ];

        $response = $this->postJson('/api/register', $userData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'user' => ['id', 'name', 'email', 'role']
                 ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Тестовый Пользователь'
        ]);
    }

    /**
     * Сценарий FUNC-POS-02: Вход зарегистрированного пользователя
     */
    public function test_user_can_login()
    {
        User::create([
            'name' => 'Тестовый Пользователь',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'phone' => '+79001234567',
            'city' => 'Москва',
            'role' => 'user'
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'user' => ['id', 'name', 'email', 'phone', 'city', 'role']
                 ]);
    }

    /**
     * Сценарий FUNC-NEG-VAL-01: Регистрация с пустыми обязательными полями
     */
    public function test_register_validation_errors()
    {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /**
     * Сценарий FUNC-NEG-ROL-01: Попытка доступа к админ-панели пользователем с ролью user
     */
    public function test_user_cannot_access_admin_panel()
    {
        $user = User::create([
            'name' => 'Обычный Пользователь',
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
            'role' => 'user'
        ]);

        $this->actingAs($user);

        $response = $this->getJson('/api/admin/orders');

        $response->assertStatus(403);
    }

    /**
     * Сценарий FUNC-NEG-ROL-02: Попытка создания товара пользователем с ролью manager
     */
    public function test_manager_cannot_create_product()
    {
        $manager = User::create([
            'name' => 'Менеджер',
            'email' => 'manager@example.com',
            'password' => bcrypt('password123'),
            'role' => 'manager'
        ]);

        $this->actingAs($manager);

        $productData = [
            'title' => 'Новая сумка',
            'price' => 5000,
            'brand_id' => 1
        ];

        $response = $this->postJson('/api/products', $productData);

        $response->assertStatus(403);
    }
}