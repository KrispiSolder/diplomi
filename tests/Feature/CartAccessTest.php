<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_cart_to_login(): void
    {
        $response = $this->get('/cart');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_see_cart_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/cart');

        $response->assertStatus(200);
    }
}


