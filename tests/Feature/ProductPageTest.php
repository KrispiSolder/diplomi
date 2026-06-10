<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_page_can_be_opened(): void
    {
        $product = Product::factory()->create();

        $response = $this->get("/product/{$product->id}");

        $response->assertStatus(200);
        $response->assertSee($product->name);
    }
}


