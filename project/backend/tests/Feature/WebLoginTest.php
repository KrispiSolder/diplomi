<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WebAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест проверки авторизации существующего пользователя
     */
    public function test_user_can_login()
    {
        // Создаём тестового пользователя в базе данных
        $user = User::create([
            'name' => 'Тестовый Пользователь',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'role' => 'user'
        ]);

        // Отправляем POST-запрос на эндпоинт авторизации
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        // Проверяем, что запрос вернул статус 200
        $response->assertStatus(200);

        // Проверяем, что в ответе присутствуют success и данные пользователя
        $response->assertJsonStructure([
            'success',
            'message',
            'user' => ['id', 'name', 'email', 'role']
        ]);

        // Проверяем, что пользователь авторизован в системе
        $this->assertAuthenticated();
    }
}