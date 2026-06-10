<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;


class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Ольга',
                'password' => '1234567890',
                'delivery_address' => 'ул. Пушкина д.12',
                'role' => 'user',
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@plantform.ru'],
            [
                'name' => 'Администратор',
                'password' => '0987654321',
                'delivery_address' => null,
                'role' => 'admin',
            ]
        );
    }
}
